<?php

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function makeNote(User $u, array $attrs = []): Note
{
    test()->actingAs($u)->post('/notes', $attrs + ['title' => 'Laravel queues', 'content' => 'Queues defer slow jobs like sending email.', 'note_type' => 'study', 'tags' => 'laravel, backend']);
    return $u->notes()->latest('id')->first();
}

test('user can create, view, update and trash a note', function () {
    $u = User::factory()->create();
    $note = makeNote($u);
    expect($note->tags()->pluck('name')->all())->toEqualCanonicalizing(['laravel', 'backend']);
    expect($note->chunks()->count())->toBeGreaterThan(0);

    $this->get("/notes/{$note->id}")->assertOk()->assertSee('Laravel queues');
    $this->put("/notes/{$note->id}", ['title' => 'Queues v2', 'content' => 'Updated body', 'note_type' => 'text'])->assertRedirect();
    expect($note->fresh()->title)->toBe('Queues v2');
    expect($note->chunks()->first()->content)->toContain('Updated body');

    $this->delete("/notes/{$note->id}")->assertRedirect();
    expect(Note::withTrashed()->find($note->id)->trashed())->toBeTrue();
    $this->post("/notes/{$note->id}/restore")->assertRedirect();
    expect($note->fresh()->trashed())->toBeFalse();
});

test('search finds notes by keyword', function () {
    $u = User::factory()->create();
    makeNote($u);
    makeNote($u, ['title' => 'Cooking', 'content' => 'Pasta recipe', 'note_type' => 'text']);
    $this->get('/notes?q=queues')->assertSee('Laravel queues')->assertDontSee('Cooking');
});

test('user A cannot access user B records', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $note = makeNote($b);
    $cat = $b->categories()->create(['name' => 'Private']);
    $conv = $b->conversations()->create(['title' => 'secret']);

    $this->actingAs($a);
    $this->get("/notes/{$note->id}")->assertForbidden();
    $this->put("/notes/{$note->id}", ['title' => 'x', 'content' => 'x', 'note_type' => 'text'])->assertForbidden();
    $this->delete("/notes/{$note->id}")->assertForbidden();
    $this->delete("/categories/{$cat->id}")->assertForbidden();
    $this->get("/brain/conversations/{$conv->id}")->assertForbidden();
    $this->post("/brain/conversations/{$conv->id}/messages", ['content' => 'hi'])->assertForbidden();
    // cannot attach someone else's category
    $this->post('/notes', ['title' => 't', 'content' => 'c', 'note_type' => 'text', 'category_id' => $cat->id])->assertSessionHasErrors('category_id');
    $this->get('/notes')->assertDontSee('Laravel queues');
});

test('brain AI answers with retrieved note sources and never leaks other users notes', function () {
    Http::fake(['*/api/chat' => Http::response(['message' => ['content' => 'Queues defer jobs [Note #1].']])]);
    $a = User::factory()->create();
    $b = User::factory()->create();
    makeNote($b, ['title' => 'B secret queues', 'content' => 'queues secret of B', 'note_type' => 'text']);
    $note = makeNote($a);

    $conv = $a->conversations()->create(['title' => 'New conversation']);
    $this->actingAs($a)->postJson("/brain/conversations/{$conv->id}/messages", ['content' => 'What do queues do?'])->assertOk();

    $reply = $conv->messages()->where('role', 'assistant')->first();
    expect($reply->status)->toBe('complete');
    expect(collect($reply->metadata['sources'])->pluck('note_id')->all())->toBe([$note->id]);
    Http::assertSent(fn ($r) => str_contains(json_encode($r->data()), 'Queues defer slow jobs') && ! str_contains(json_encode($r->data()), 'secret of B'));
    expect($conv->fresh()->title)->toBe('What do queues do?');
});

test('provider failure is shown as an error message, not a crash', function () {
    Http::fake(['*' => Http::response('down', 500)]);
    $u = User::factory()->create();
    $conv = $u->conversations()->create(['title' => 'x']);
    $this->actingAs($u)->post("/brain/conversations/{$conv->id}/messages", ['content' => 'hello'])->assertRedirect();
    expect($conv->messages()->where('role', 'assistant')->first()->status)->toBe('error');
});

test('save to brain prefills note form for review', function () {
    $u = User::factory()->create();
    $conv = $u->conversations()->create(['title' => 'Chat']);
    $m = $conv->messages()->create(['role' => 'assistant', 'content' => 'Useful answer']);
    $this->actingAs($u)->get("/brain/messages/{$m->id}/save-as-note")->assertRedirectContains('/notes/create');
    $this->get('/notes/create?content=Useful+answer')->assertSee('Review and edit it before saving');
});

test('api key is stored encrypted and never rendered', function () {
    $u = User::factory()->create();
    $this->actingAs($u)->put('/settings/ai', ['provider' => 'openai_compatible', 'model' => 'gpt-4o-mini', 'api_key' => 'sk-supersecret'])->assertRedirect();
    $raw = \DB::table('ai_settings')->where('user_id', $u->id)->value('api_key_encrypted');
    expect($raw)->not->toContain('sk-supersecret');
    expect($u->fresh()->aiSetting->api_key_encrypted)->toBe('sk-supersecret');
    $this->get('/settings/ai')->assertOk()->assertDontSee('sk-supersecret');
});

test('notes cannot link to themselves', function () {
    $u = User::factory()->create();
    $n = makeNote($u);
    $this->post("/notes/{$n->id}/links", ['target_note_id' => $n->id])->assertStatus(422);
});
