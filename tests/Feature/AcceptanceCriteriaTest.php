<?php

/**
 * Technical Implementation Specification, section 13: acceptance criteria AC-01 .. AC-12,
 * plus the requirements of sections 8-12 that were closed after the spec audit.
 * AI providers are faked; no network or API keys required.
 */

use App\Models\Embedding;
use App\Models\Note;
use App\Models\NoteChunk;
use App\Models\User;
use App\Rules\SafeAiEndpoint;
use Illuminate\Support\Facades\Http;

function chatFake(string $reply = 'Answer [Note #1].', int $status = 200): void
{
    Http::fake(['*/api/chat' => $status === 200 ? Http::response(['message' => ['content' => $reply]]) : Http::response(['error' => 'down'], $status)]);
}

function addNote(User $u, array $a = []): Note
{
    test()->actingAs($u)->post('/notes', $a + ['title' => 'Photosynthesis', 'content' => 'Plants convert light into chemical energy in chloroplasts.', 'note_type' => 'study', 'tags' => 'biology, exam']);
    return $u->notes()->latest('id')->first();
}

test('AC-01 register and log in', function () {
    $this->post('/register', ['name' => 'Ana', 'email' => 'ana@x.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    $this->post('/logout');
    $this->post('/login', ['email' => 'ana@x.com', 'password' => 'password123'])->assertRedirect('/dashboard');
    $this->get('/dashboard')->assertOk();
});

test('AC-02 create, edit, delete note persists and shows in list', function () {
    $u = User::factory()->create();
    $n = addNote($u);
    $this->get('/notes')->assertSee('Photosynthesis');
    $this->put("/notes/{$n->id}", ['title' => 'Photosynthesis v2', 'content' => 'Updated.', 'note_type' => 'study'])->assertRedirect();
    $this->get('/notes')->assertSee('Photosynthesis v2');
    $this->delete("/notes/{$n->id}");
    $this->get('/notes')->assertDontSee('Photosynthesis v2');
    $this->get('/notes?trash=1')->assertSee('Photosynthesis v2');
});

test('AC-03 category and multiple tags can be assigned', function () {
    $u = User::factory()->create();
    $c = $u->categories()->create(['name' => 'Biology']);
    $n = addNote($u, ['category_id' => $c->id, 'tags' => 'plants, energy, exam']);
    expect($n->category_id)->toBe($c->id)->and($n->tags()->count())->toBe(3);
});

test('AC-04 search returns matches only from the current user', function () {
    $a = User::factory()->create(); $b = User::factory()->create();
    addNote($b, ['title' => 'Chloroplast secrets of B', 'content' => 'chloroplasts', 'note_type' => 'text']);
    addNote($a);
    $this->actingAs($a)->get('/notes?q=chloroplasts')->assertSee('Photosynthesis')->assertDontSee('secrets of B');
    $this->actingAs($a)->get('/notes?q=chloroplasts&mode=exact')->assertSee('Photosynthesis')->assertDontSee('secrets of B');
});

test('AC-05 a user cannot view, update or delete another users resources', function () {
    $a = User::factory()->create(); $b = User::factory()->create();
    $n = addNote($b); $tag = $b->tags()->first(); $conv = $b->conversations()->create(['title' => 'b']);
    $m = $conv->messages()->create(['role' => 'assistant', 'content' => 'secret']);
    $this->actingAs($a);
    $this->get("/notes/{$n->id}")->assertForbidden();
    $this->put("/notes/{$n->id}", ['title' => 'x', 'content' => 'x', 'note_type' => 'text'])->assertForbidden();
    $this->delete("/notes/{$n->id}")->assertForbidden();
    $this->delete("/tags/{$tag->id}")->assertForbidden();
    $this->post("/capture/{$n->id}/convert")->assertForbidden();
    $this->post("/brain/messages/{$m->id}/save-as-note")->assertForbidden();
    $this->post("/notes/{$n->id}/summary-as-note", ['summary' => 'x'])->assertForbidden();
});

test('AC-06 configured provider returns a response', function () {
    chatFake('Hello from the model');
    $u = User::factory()->create(); $c = $u->conversations()->create(['title' => 'New conversation']);
    $this->actingAs($u)->postJson("/brain/conversations/{$c->id}/messages", ['content' => 'hi'])->assertOk()->assertSee('Hello from the model');
});

test('AC-07 relevant current-user notes are supplied as context, separate from system rules', function () {
    chatFake();
    $u = User::factory()->create(); addNote($u);
    $other = User::factory()->create(); addNote($other, ['title' => 'Other chloroplasts', 'content' => 'chloroplasts of other user', 'note_type' => 'text']);
    $c = $u->conversations()->create(['title' => 'x']);
    $this->actingAs($u)->postJson("/brain/conversations/{$c->id}/messages", ['content' => 'What happens in chloroplasts?']);

    Http::assertSent(function ($req) {
        $msgs = $req['messages'];
        return $msgs[0]['role'] === 'system' && str_contains($msgs[0]['content'], 'untrusted')   // rules
            && $msgs[1]['role'] === 'system' && str_contains($msgs[1]['content'], 'Plants convert light') // excerpts
            && ! str_contains(json_encode($msgs), 'of other user')
            && end($msgs)['role'] === 'user' && end($msgs)['content'] === 'What happens in chloroplasts?';
    });
});

test('AC-08 response stores and displays note and chunk references', function () {
    chatFake();
    $u = User::factory()->create(); $n = addNote($u);
    $c = $u->conversations()->create(['title' => 'x']);
    $html = $this->actingAs($u)->postJson("/brain/conversations/{$c->id}/messages", ['content' => 'chloroplasts?'])->json('message');
    $src = $c->messages()->where('role', 'assistant')->first()->metadata['sources'][0];
    expect($src['note_id'])->toBe($n->id)
        ->and($src['chunk_id'])->toBe(NoteChunk::where('note_id', $n->id)->value('id'))
        ->and($html)->toContain(route('notes.show', $n->id));
});

test('AC-09 AI output is proposed, previewed and saved only after confirmation', function () {
    $u = User::factory()->create(); $cat = $u->categories()->create(['name' => 'Biology']);
    $c = $u->conversations()->create(['title' => 'Plants']);
    $m = $c->messages()->create(['role' => 'assistant', 'content' => 'Photosynthesis turns light into sugar.']);
    chatFake('{"title":"How photosynthesis works","category":"Biology","tags":["plants","energy"],"type":"study"}');

    $before = Note::count();
    $this->actingAs($u)->post("/brain/messages/{$m->id}/save-as-note")->assertRedirect(route('notes.create'));
    expect(Note::count())->toBe($before); // nothing saved yet

    $this->get(route('notes.create'))->assertOk()
        ->assertSee('PROPOSED NOTE')->assertSee('How photosynthesis works')
        ->assertSee('plants, energy')->assertSee('<option value="'.$cat->id.'" selected', false);

    // user edits and confirms
    $this->post('/notes', ['title' => 'Photosynthesis (edited)', 'content' => 'Photosynthesis turns light into sugar.', 'note_type' => 'study', 'category_id' => $cat->id, 'tags' => 'plants'])->assertRedirect();
    $saved = $u->notes()->where('title', 'Photosynthesis (edited)')->first();
    expect($saved)->not->toBeNull()->and($saved->chunks()->count())->toBeGreaterThan(0); // indexed for retrieval
    $this->get('/notes?q=sugar&mode=exact')->assertSee('Photosynthesis (edited)');
});

test('AC-09 proposal still works when the AI cannot label the note', function () {
    chatFake('', 500);
    $u = User::factory()->create(); $c = $u->conversations()->create(['title' => 'Plants']);
    $m = $c->messages()->create(['role' => 'assistant', 'content' => 'Useful answer']);
    $this->actingAs($u)->post("/brain/messages/{$m->id}/save-as-note");
    $this->get(route('notes.create'))->assertSee('PROPOSED NOTE')->assertSee('Useful answer')->assertSee('Brain AI: Plants');
});

test('AC-10 provider can be configured, tested and selected', function () {
    Http::fake([
        'api.example-ai.com/v1/models' => Http::response(['data' => [['id' => 'model-a'], ['id' => 'model-b']]]),
        'api.example-ai.com/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'cloud reply']]], 'model' => 'model-a']),
    ]);
    $u = User::factory()->create();
    $this->actingAs($u)->post('/settings/ai/test', ['provider' => 'openai_compatible', 'base_url' => 'https://api.example-ai.com/v1', 'model' => 'model-a', 'api_key' => 'k'])
        ->assertSessionHas('status', fn ($s) => str_contains($s, '2 model(s)'));
    $this->put('/settings/ai', ['provider' => 'openai_compatible', 'base_url' => 'https://api.example-ai.com/v1', 'model' => 'model-a', 'api_key' => 'k'])->assertRedirect();
    $c = $u->conversations()->create(['title' => 'x']);
    $this->postJson("/brain/conversations/{$c->id}/messages", ['content' => 'hi'])->assertSee('cloud reply');
    $this->get("/brain/conversations/{$c->id}")->assertSee('OpenAI-compatible API · model-a');
});

test('AC-11 provider failure shows an error and retains the users message; failed turn is excluded from later context', function () {
    chatFake('', 500);
    $u = User::factory()->create(); $c = $u->conversations()->create(['title' => 'x']);
    $this->actingAs($u)->postJson("/brain/conversations/{$c->id}/messages", ['content' => 'first question'])->assertOk()->assertSee('ERR');
    expect($c->messages()->where('role', 'user')->value('content'))->toBe('first question')
        ->and($c->messages()->where('role', 'assistant')->value('status'))->toBe('error');

    chatFake('fine now');
    $this->postJson("/brain/conversations/{$c->id}/messages", ['content' => 'second question']);
    Http::assertSent(fn ($r) => ! str_contains(json_encode($r['messages']), 'AI provider error') && ! str_contains(json_encode($r['messages']), 'Ollama error'));
});

test('AC-12 changed content is re-indexed and stale retrieval data is not used', function () {
    Http::fake(['*/api/embed' => fn ($req) => Http::response(['embeddings' => array_map(fn ($t) => [strlen($t) % 7 + 1, 1, 1], (array) $req['input'])])]);
    $u = User::factory()->create();
    $u->aiSetting()->create(['provider' => 'ollama', 'model' => 'x', 'embedding_model' => 'e']);
    $n = addNote($u, ['title' => 'Topic', 'content' => 'Mitochondria are the powerhouse.', 'note_type' => 'text']);
    $oldChunk = $n->chunks()->value('id');
    $this->put("/notes/{$n->id}", ['title' => 'Topic', 'content' => 'Ribosomes build proteins.', 'note_type' => 'text']);

    expect(NoteChunk::find($oldChunk))->toBeNull()
        ->and(Embedding::where('note_chunk_id', $oldChunk)->count())->toBe(0)
        ->and($n->chunks()->value('content'))->toContain('Ribosomes');
    // Retrieval only ever sees the current chunks: no excerpt with the old text can come back.
    $hits = app(\App\Services\Knowledge\RetrievalService::class)->retrieve($u->fresh(), 'mitochondria powerhouse');
    expect($hits->pluck('chunk.content')->implode(' '))->not->toContain('Mitochondria');
    $this->get('/notes?q=ribosomes&mode=exact')->assertSee('Topic');
    $this->get('/notes?q=mitochondria&mode=exact')->assertDontSee('Topic');

    $this->delete("/notes/{$n->id}");
    $this->delete("/notes/{$n->id}/force");
    expect(NoteChunk::where('note_id', $n->id)->count())->toBe(0)->and(Embedding::where('user_id', $u->id)->count())->toBe(0);
});

// ── Requirements closed after the spec audit ──────────────────────────────

test('spec 8.2/10: retrieved context is bounded and citations match what was sent', function () {
    config(['services.ai.max_context_chars' => 700]);
    chatFake();
    $u = User::factory()->create();
    foreach (range(1, 6) as $i) addNote($u, ['title' => "Cells $i", 'content' => str_repeat("cells membrane nucleus $i. ", 30), 'note_type' => 'text']);
    $c = $u->conversations()->create(['title' => 'x']);
    $this->actingAs($u)->postJson("/brain/conversations/{$c->id}/messages", ['content' => 'cells membrane nucleus']);

    $sent = null; Http::assertSent(function ($r) use (&$sent) { $sent = $r['messages'][1]['content']; return true; });
    $sources = $c->messages()->where('role', 'assistant')->first()->metadata['sources'];
    expect(mb_strlen($sent))->toBeLessThan(1200)
        ->and(count($sources))->toBeLessThan(6)
        ->and(substr_count($sent, '<note id='))->toBe(count($sources));
});

test('spec 11: AI endpoints must be HTTPS and not internal network targets', function () {
    expect(SafeAiEndpoint::check('https://api.groq.com/openai/v1'))->toBeNull()
        ->and(SafeAiEndpoint::check('http://localhost:11434'))->toBeNull()
        ->and(SafeAiEndpoint::check('http://api.groq.com/v1'))->toContain('HTTPS')
        ->and(SafeAiEndpoint::check('https://169.254.169.254/latest'))->toContain('private or reserved')
        ->and(SafeAiEndpoint::check('https://10.0.0.5/v1'))->toContain('private or reserved')
        ->and(SafeAiEndpoint::check('https://192.168.1.10/v1'))->toContain('private or reserved')
        ->and(SafeAiEndpoint::check('file:///etc/passwd'))->not->toBeNull()
        ->and(SafeAiEndpoint::check('https://user:pw@api.x.com'))->toContain('credentials');

    config(['services.ai.allow_local_endpoints' => false]);
    expect(SafeAiEndpoint::check('http://localhost:11434'))->toContain('disabled');
    config(['services.ai.allowed_hosts' => 'gpu.lan']);
    expect(SafeAiEndpoint::check('http://gpu.lan:11434'))->toBeNull();

    $u = User::factory()->create();
    $this->actingAs($u)->put('/settings/ai', ['provider' => 'openai_compatible', 'base_url' => 'https://169.254.169.254/v1', 'model' => 'm'])->assertSessionHasErrors('base_url');
});

test('spec 12: quick capture page lists inbox and converts items to notes', function () {
    $u = User::factory()->create();
    $this->actingAs($u)->post('/capture', ['content' => "Idea: flashcards app\nmore detail"])->assertRedirect();
    $item = $u->notes()->first();
    expect($item->note_type)->toBe('inbox')->and($item->title)->toBe('Idea: flashcards app');
    $this->get('/capture')->assertOk()->assertSee('Idea: flashcards app')->assertSee('Convert to note');
    $this->post("/capture/{$item->id}/convert")->assertRedirect(route('notes.edit', $item));
    expect($item->fresh()->note_type)->toBe('text');
    $this->get('/capture')->assertSee('inbox zero');
});

test('spec 12: notes can be sorted', function () {
    $u = User::factory()->create();
    addNote($u, ['title' => 'Zebra', 'content' => 'z', 'note_type' => 'text']);
    $this->travel(1)->minutes();
    addNote($u, ['title' => 'Apple', 'content' => 'a', 'note_type' => 'text']);
    $html = $this->actingAs($u)->get('/notes?sort=title')->getContent();
    expect(strpos($html, 'Apple'))->toBeLessThan(strpos($html, 'Zebra'));
    $html = $this->get('/notes?sort=created')->getContent();
    expect(strpos($html, 'Apple'))->toBeLessThan(strpos($html, 'Zebra')); // newest first
});

test('spec 6: tags can be created and deleted without losing notes', function () {
    $u = User::factory()->create(); $n = addNote($u, ['tags' => 'temp']);
    $this->actingAs($u)->post('/tags', ['name' => 'Fresh'])->assertRedirect();
    expect($u->tags()->where('name', 'fresh')->exists())->toBeTrue();
    $this->delete('/tags/'.$u->tags()->where('name', 'temp')->value('id'))->assertRedirect();
    expect($n->fresh())->not->toBeNull()->and($n->tags()->count())->toBe(0);
});

test('spec 8.3: UI describes the actual retrieval method', function () {
    $u = User::factory()->create(); $c = $u->conversations()->create(['title' => 'x']);
    $this->actingAs($u)->get('/notes')->assertSee('Keyword search in title or content');
    $this->get("/brain/conversations/{$c->id}")->assertSee('retrieval: keyword')->assertDontSee('keyword + semantic');
    $u->aiSetting()->create(['provider' => 'ollama', 'model' => 'x', 'embedding_model' => 'e']);
    $this->actingAs($u->fresh())->get("/brain/conversations/{$c->id}")->assertSee('retrieval: keyword + semantic');
});

test('spec 9: proposal parser repairs near-JSON from small models and maps categories', function () {
    $parse = fn ($s) => \App\Services\Knowledge\NoteProposalService::parseJson($s);
    expect($parse('{"title": "x", "category": Databases, "tags": ["a"]}'))->toBe(['title' => 'x', 'category' => 'Databases', 'tags' => ['a']])
        ->and($parse('Sure! {"title": "y", "tags": ["b",],}'))->toBe(['title' => 'y', 'tags' => ['b']])
        ->and($parse('no json here'))->toBeNull();

    $match = fn ($g) => \App\Services\Knowledge\NoteProposalService::matchCategory($g, [1 => 'Databases', 2 => 'Study Skills', 3 => 'Personal']);
    expect($match('Database'))->toBe(1)->and($match('study'))->toBe(2)->and($match('Personl'))->toBe(3)
        ->and($match('Productivity'))->toBeNull()->and($match('none'))->toBeNull();
});

test('spec 9: AI summary can be proposed as a note and inherits the category', function () {
    $u = User::factory()->create(); $cat = $u->categories()->create(['name' => 'Biology']);
    $n = addNote($u, ['category_id' => $cat->id]);
    chatFake('not json at all');
    $this->actingAs($u)->post("/notes/{$n->id}/summary-as-note", ['summary' => '- plants make sugar'])->assertRedirect(route('notes.create'));
    $this->get(route('notes.create'))->assertSee('PROPOSED NOTE')->assertSee('Summary: Photosynthesis')
        ->assertSee('<option value="'.$cat->id.'" selected', false);
    expect($u->notes()->count())->toBe(1); // still nothing saved
});
