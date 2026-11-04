<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Note;
use App\Models\Tag;
use App\Services\AI\AIProviderException;
use App\Services\Knowledge\NoteIndexingService;
use App\Services\Knowledge\RetrievalService;
use App\Services\Knowledge\NoteSummarizationService;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(protected NoteIndexingService $indexer) {}

    public function index(Request $request, RetrievalService $retrieval)
    {
        $user = $request->user();
        $trash = $request->boolean('trash');
        $q = $request->string('q')->toString();
        $mode = $request->input('mode', 'smart');
        // Smart mode: hybrid keyword + meaning-based ranking. Exact mode: plain substring match.
        $ranked = ($q !== '' && ! $trash && $mode === 'smart') ? $retrieval->rankNoteIds($user, $q) : null;

        $notes = ($trash ? $user->notes()->onlyTrashed() : $user->notes())
            ->with(['category', 'tags'])
            ->when($ranked !== null, fn ($b) => $b->whereIn('id', $ranked->keys()->all() ?: [0]))
            ->when($ranked === null, fn ($b) => $b->search($q))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('tag'), fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('tags.id', $request->integer('tag'))))
            ->when($request->filled('type'), fn ($q) => $q->where('note_type', $request->string('type')))
            ->when($request->boolean('favorites'), fn ($q) => $q->where('is_favorite', true))
            ->when($ranked?->isNotEmpty(), fn ($b) => $b->orderByRaw('CASE id '.$ranked->keys()->map(fn ($id, $i) => 'WHEN '.(int) $id.' THEN '.$i)->implode(' ').' END'))
            ->when($ranked === null, fn ($b) => $b->orderByDesc('is_pinned')->latest('updated_at'))
            ->paginate(12)->withQueryString();

        return view('notes.index', [
            'notes' => $notes,
            'trash' => $trash,
            'matchMethods' => $ranked ?? collect(),
            'semantic' => app(\App\Services\Knowledge\EmbeddingService::class)->enabledFor($user),
            'categories' => $user->categories()->orderBy('name')->get(),
            'tags' => $user->tags()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('notes.form', [
            'note' => new Note(['note_type' => 'text', 'title' => $request->string('title'), 'content' => $request->string('content')]),
            'categories' => $request->user()->categories()->orderBy('name')->get(),
        ]);
    }

    public function store(NoteRequest $request)
    {
        $note = $request->user()->notes()->create($request->safe()->except('tags') + $this->flags($request));
        $this->syncTags($request, $note);
        $this->indexer->index($note);
        return redirect()->route('notes.show', $note)->with('status', 'Note saved.');
    }

    public function quickCapture(Request $request)
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:20000']]);
        $title = str($data['content'])->before("\n")->limit(80)->toString();
        $note = $request->user()->notes()->create(['title' => $title ?: 'Quick capture', 'content' => $data['content'], 'note_type' => 'inbox']);
        $this->indexer->index($note);
        return back()->with('status', 'Captured to inbox.');
    }

    public function show(Note $note)
    {
        $this->authorize('view', $note);
        $note->load(['category', 'tags', 'outgoingLinks.target', 'incomingLinks.source']);
        return view('notes.show', [
            'note' => $note,
            'linkable' => $note->user->notes()->whereKeyNot($note->id)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function edit(Request $request, Note $note)
    {
        $this->authorize('update', $note);
        return view('notes.form', ['note' => $note->load('tags'), 'categories' => $request->user()->categories()->orderBy('name')->get()]);
    }

    public function update(NoteRequest $request, Note $note)
    {
        $this->authorize('update', $note);
        $note->update($request->safe()->except('tags') + $this->flags($request));
        $this->syncTags($request, $note);
        if ($note->wasChanged(['title', 'content'])) $this->indexer->index($note);
        return redirect()->route('notes.show', $note)->with('status', 'Note updated.');
    }

    public function toggle(Request $request, Note $note, string $flag)
    {
        $this->authorize('update', $note);
        abort_unless(in_array($flag, ['is_favorite', 'is_pinned'], true), 404);
        $note->update([$flag => ! $note->$flag]);
        return back();
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);
        $note->delete();
        return redirect()->route('notes.index')->with('status', 'Note moved to trash.');
    }

    public function restore(Request $request, int $id)
    {
        $note = $request->user()->notes()->onlyTrashed()->findOrFail($id);
        $note->restore();
        return redirect()->route('notes.show', $note)->with('status', 'Note restored.');
    }

    public function forceDelete(Request $request, int $id)
    {
        $request->user()->notes()->onlyTrashed()->findOrFail($id)->forceDelete();
        return back()->with('status', 'Note permanently deleted.');
    }

    public function summarize(Request $request, Note $note, NoteSummarizationService $svc)
    {
        $this->authorize('view', $note);
        try {
            $summary = $svc->summarize($request->user(), $note);
        } catch (AIProviderException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('summary', $summary);
    }

    public function link(Request $request, Note $note)
    {
        $this->authorize('update', $note);
        $data = $request->validate([
            'target_note_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('notes', 'id')->where('user_id', $request->user()->id)->whereNull('deleted_at')],
            'label' => ['nullable', 'string', 'max:100'],
        ]);
        abort_if((int) $data['target_note_id'] === $note->id, 422, 'A note cannot link to itself.');
        $note->outgoingLinks()->firstOrCreate(
            ['target_note_id' => $data['target_note_id']],
            ['label' => $data['label'] ?? null, 'user_id' => $request->user()->id],
        );
        return back()->with('status', 'Link added.');
    }

    protected function flags(Request $request): array
    {
        return ['is_favorite' => $request->boolean('is_favorite'), 'is_pinned' => $request->boolean('is_pinned')];
    }

    protected function syncTags(Request $request, Note $note): void
    {
        $names = collect(explode(',', (string) $request->input('tags')))->map(fn ($t) => trim(mb_strtolower($t)))->filter()->unique()->take(20);
        $ids = $names->map(fn ($n) => $request->user()->tags()->firstOrCreate(['name' => mb_substr($n, 0, 50)])->id);
        $note->tags()->sync($ids);
    }
}
