<?php

use App\Models\Embedding;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Fake embedding model: maps words to 3 "concept" axes so synonyms land close together.
 * axis0 = vehicles, axis1 = food, axis2 = programming.
 */
function fakeVector(string $text): array
{
    $t = strtolower($text);
    $v = [0.01, 0.01, 0.01];
    foreach (['car' => 0, 'vehicle' => 0, 'automobile' => 0, 'engine' => 0, 'pasta' => 1, 'recipe' => 1, 'cook' => 1, 'php' => 2, 'code' => 2, 'laravel' => 2] as $w => $axis) {
        $v[$axis] += substr_count($t, $w);
    }
    return $v;
}

beforeEach(function () {
    Http::fake([
        '*/api/embed' => fn ($req) => Http::response(['embeddings' => array_map('fakeVector', (array) $req['input'])]),
        '*/api/chat' => Http::response(['message' => ['content' => 'ok']]),
    ]);
    $this->user = User::factory()->create();
    $this->user->aiSetting()->create(['provider' => 'ollama', 'model' => 'llama3.2', 'embedding_model' => 'fake-embed']);
    $this->actingAs($this->user);
    foreach ([
        ['Vehicle maintenance', 'Change the engine oil every 5000 km.'],
        ['Dinner ideas', 'A quick pasta recipe with garlic.'],
        ['Framework notes', 'Laravel code structure and php tips.'],
    ] as [$title, $content]) {
        $this->post('/notes', ['title' => $title, 'content' => $content, 'note_type' => 'text']);
    }
});

test('notes are embedded on save and re-embedded only when changed', function () {
    expect(Embedding::count())->toBe(3);
    $note = $this->user->notes()->first();
    $before = Embedding::pluck('id')->all();
    $this->put("/notes/{$note->id}", ['title' => $note->title, 'content' => $note->content, 'note_type' => 'study']);
    expect(Embedding::pluck('id')->all())->toBe($before); // content unchanged, vectors kept
    $this->put("/notes/{$note->id}", ['title' => $note->title, 'content' => 'New car tyres.', 'note_type' => 'text']);
    expect(Embedding::count())->toBe(3)->and(Embedding::pluck('id')->all())->not->toBe($before);
});

test('search finds notes by meaning even without shared keywords', function () {
    // "automobile" appears in no note; it should still find the vehicle note via semantics.
    $this->get('/notes?q=automobile')
        ->assertOk()
        ->assertSee('Vehicle maintenance')
        ->assertDontSee('Dinner ideas')
        ->assertSee('meaning');
    // Exact mode disables semantics.
    $this->get('/notes?q=automobile&mode=exact')->assertDontSee('Vehicle maintenance');
});

test('brain AI uses semantically retrieved notes as sources', function () {
    $conv = $this->user->conversations()->create(['title' => 'x']);
    $this->postJson("/brain/conversations/{$conv->id}/messages", ['content' => 'How should I care for my automobile?'])->assertOk();
    $sources = collect($conv->messages()->where('role', 'assistant')->first()->metadata['sources']);
    expect($sources->first()['title'])->toBe('Vehicle maintenance');
    expect($sources->first()['method'])->toBe('semantic');
});

test('semantic search never returns other users notes', function () {
    $other = User::factory()->create();
    $other->aiSetting()->create(['provider' => 'ollama', 'model' => 'x', 'embedding_model' => 'fake-embed']);
    $this->actingAs($other)->get('/notes?q=automobile')->assertDontSee('Vehicle maintenance');
});

test('embedding outage falls back to keyword search', function () {
    Http::fake(['*' => Http::response('down', 500)]);
    $this->get('/notes?q=pasta')->assertOk()->assertSee('Dinner ideas');
});

test('reindex embeds notes created before semantic search was enabled', function () {
    Embedding::query()->delete();
    $this->post('/settings/ai/reindex')->assertRedirect();
    expect(Embedding::count())->toBe(3);
});
