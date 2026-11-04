<?php

use App\Models\User;
use App\Services\Knowledge\RelatedNotesService;
use Illuminate\Support\Facades\Http;

require_once __DIR__.'/SemanticSearchTest.php'; // reuse fakeVector()

function graphUser(bool $semantic = true): User
{
    Http::fake(['*/api/embed' => fn ($req) => Http::response(['embeddings' => array_map('fakeVector', (array) $req['input'])])]);
    $u = User::factory()->create();
    $u->aiSetting()->create(['provider' => 'ollama', 'model' => 'x', 'embedding_model' => $semantic ? 'fake-embed' : null]);
    test()->actingAs($u);
    foreach ([
        ['Car engine', 'engine oil for the car', 'garage'],
        ['Automobile insurance', 'vehicle insurance renewal', 'admin'],
        ['Pasta night', 'a recipe to cook pasta', 'food, weekend'],
        ['Laravel tips', 'php code in laravel', 'php, weekend'],
        ['Composer basics', 'php code packages', 'php'],
    ] as [$t, $c, $tag]) {
        test()->post('/notes', ['title' => $t, 'content' => $c, 'note_type' => 'text', 'tags' => $tag]);
    }
    return $u;
}

test('suggests semantically related notes with a reason', function () {
    $u = graphUser();
    $car = $u->notes()->where('title', 'Car engine')->first();
    $s = app(RelatedNotesService::class)->suggestFor($car);
    expect($s->first()['note']->title)->toBe('Automobile insurance')
        ->and($s->first()['reason'])->toContain('similar meaning')
        ->and($s->pluck('note.title'))->not->toContain('Pasta night');
});

test('falls back to shared tags without embeddings', function () {
    $u = graphUser(semantic: false);
    $laravel = $u->notes()->where('title', 'Laravel tips')->first();
    $s = app(RelatedNotesService::class)->suggestFor($laravel);
    expect($s->pluck('note.title')->sort()->values()->all())->toBe(['Composer basics', 'Pasta night'])
        ->and($s->firstWhere('note.title', 'Composer basics')['reason'])->toContain('#php')
        ->and($s->pluck('note.title'))->not->toContain('Car engine');
});

test('already linked notes are not suggested again', function () {
    $u = graphUser();
    [$car, $auto] = [$u->notes()->where('title', 'Car engine')->first(), $u->notes()->where('title', 'Automobile insurance')->first()];
    $this->post("/notes/{$car->id}/links", ['target_note_id' => $auto->id, 'label' => 'related'])->assertRedirect();
    expect(app(RelatedNotesService::class)->suggestFor($car)->pluck('note.id'))->not->toContain($auto->id);
    $this->get("/notes/{$car->id}")->assertOk()->assertSee('Automobile insurance');
});

test('graph contains link, tag and semantic edges', function () {
    $u = graphUser();
    $car = $u->notes()->where('title', 'Car engine')->first();
    $pasta = $u->notes()->where('title', 'Pasta night')->first();
    $this->post("/notes/{$car->id}/links", ['target_note_id' => $pasta->id]);

    $g = app(RelatedNotesService::class)->graph($u);
    $kinds = collect($g['edges'])->pluck('kind')->unique()->sort()->values()->all();
    expect(count($g['nodes']))->toBe(5)->and($kinds)->toBe(['link', 'semantic', 'tag']);
    $this->get('/graph')->assertOk()->assertSee('Knowledge graph', false)->assertSee('Car engine');
});

test('graph never includes other users notes', function () {
    graphUser();
    $other = User::factory()->create();
    $this->actingAs($other)->get('/graph')->assertOk()->assertDontSee('Car engine');
    expect(app(RelatedNotesService::class)->graph($other)['nodes'])->toBe([]);
});

test('demo seeder builds a usable knowledge base', function () {
    Http::fake(['*' => Http::response([], 500)]); // no AI available: seeding must still work
    $this->seed(\Database\Seeders\DemoSeeder::class);
    $u = User::where('email', 'demo@example.com')->first();
    expect($u->notes()->count())->toBeGreaterThan(10)
        ->and($u->categories()->count())->toBe(5)
        ->and(\App\Models\NoteLink::where('user_id', $u->id)->count())->toBe(4);
    $this->seed(\Database\Seeders\DemoSeeder::class); // idempotent
    expect($u->notes()->count())->toBe(17);
});
