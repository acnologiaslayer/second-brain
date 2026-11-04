<?php

namespace App\Services\Knowledge;

use App\Models\Embedding;
use App\Models\Note;
use App\Models\User;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Collection;

/**
 * Suggests notes related to a given note (AI-suggested links) and builds the
 * knowledge-graph edge list. Uses note-level vectors (mean of chunk embeddings)
 * when semantic search is configured, otherwise falls back to shared tags,
 * same category and title-term overlap. Everything is scoped to one user.
 */
class RelatedNotesService
{
    public function __construct(protected AIProviderManager $ai) {}

    /**
     * Hybrid suggestion score: semantic similarity (when available) plus small boosts
     * for shared tags and same category. Each suggestion carries a human-readable reason.
     * @return Collection<int, array{note: Note, score: float, reason: string}>
     */
    public function suggestFor(Note $note, int $limit = 5): Collection
    {
        $exclude = $note->outgoingLinks()->pluck('target_note_id')
            ->merge($note->incomingLinks()->pluck('source_note_id'))->push($note->id)->unique();

        $semantic = $this->semanticNeighbours($note->user, $note->id); // note_id => sim (already thresholded)
        $myTags = $note->tags()->pluck('tags.name');
        $others = $note->user->notes()->whereNotIn('id', $exclude)->with('tags:id,name')->get();

        return $others->map(function (Note $o) use ($note, $semantic, $myTags) {
                $shared = $o->tags->pluck('name')->intersect($myTags)->values();
                $sameCat = $note->category_id && $o->category_id === $note->category_id;
                $sim = $semantic[$o->id] ?? null;

                $score = ($sim ?? 0) + 0.04 * $shared->count() + ($sameCat ? 0.03 : 0);
                if ($semantic->isEmpty()) $score = $shared->count() + ($sameCat ? 0.5 : 0); // no embeddings: structural only

                $reasons = array_filter([
                    $sim !== null ? 'similar meaning' : null,
                    $shared->isNotEmpty() ? '#'.$shared->take(2)->implode(' #') : null,
                    $sameCat ? 'same category' : null,
                ]);
                $qualifies = $sim !== null || $shared->isNotEmpty();

                return $qualifies ? ['note' => $o, 'score' => round($score, 3), 'reason' => implode(' · ', $reasons)] : null;
            })
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Graph for one user: nodes + edges of three kinds (link, tag, semantic).
     * @return array{nodes: array, edges: array}
     */
    public function graph(User $user, ?float $semanticMin = null): array
    {
        $semanticMin ??= (float) config('services.ai.graph_min_similarity', 0.6);
        $notes = $user->notes()->with(['category:id,name', 'tags:id,name'])->get(['id', 'title', 'category_id', 'note_type', 'is_pinned', 'updated_at']);
        $ids = $notes->pluck('id')->flip();

        $nodes = $notes->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'category' => $n->category?->name,
            'type' => $n->note_type,
            'pinned' => $n->is_pinned,
            'tags' => $n->tags->pluck('name')->all(),
            'url' => route('notes.show', $n),
        ])->values()->all();

        $edges = [];
        $key = fn ($a, $b) => min($a, $b).'-'.max($a, $b);

        // 1. Explicit links (strongest)
        foreach ($user->notes()->join('note_links', 'note_links.source_note_id', '=', 'notes.id')->get(['note_links.source_note_id', 'note_links.target_note_id', 'note_links.label']) as $l) {
            if (! isset($ids[$l->target_note_id])) continue;
            $edges[$key($l->source_note_id, $l->target_note_id)] = ['source' => $l->source_note_id, 'target' => $l->target_note_id, 'kind' => 'link', 'label' => $l->label];
        }

        // 2. Shared tags
        $byTag = [];
        foreach ($notes as $n) foreach ($n->tags as $t) $byTag[$t->name][] = $n->id;
        $maxShare = max(3, (int) ceil(count($notes) * 0.25));
        foreach ($byTag as $tag => $members) {
            if (count($members) > $maxShare) continue; // e.g. #exam on half the notes: noise, not structure
            for ($i = 0; $i < count($members); $i++) for ($j = $i + 1; $j < count($members); $j++) {
                $k = $key($members[$i], $members[$j]);
                $edges[$k] ??= ['source' => $members[$i], 'target' => $members[$j], 'kind' => 'tag', 'label' => '#'.$tag];
            }
        }

        // 3. Semantic similarity (AI-suggested): k-nearest neighbours per note above a floor.
        //    Per-note ranking is far more robust than one global threshold, because
        //    embedding models give every pair of texts a non-trivial baseline score.
        $vectors = $this->noteVectors($user);
        $k = (int) config('services.ai.graph_neighbours', 2);
        foreach ($vectors as $a => $va) {
            $sims = [];
            foreach ($vectors as $b => $vb) if ($a !== $b) $sims[$b] = EmbeddingService::dot($va, $vb);
            arsort($sims);
            foreach (array_slice($sims, 0, $k, true) as $b => $sim) {
                if ($sim < $semanticMin) break;
                $kk = $key($a, $b);
                if (($edges[$kk]['kind'] ?? null) !== 'link') {
                    $edges[$kk] = ['source' => $a, 'target' => $b, 'kind' => 'semantic', 'label' => round($sim, 2)];
                }
            }
        }

        return ['nodes' => $nodes, 'edges' => array_values($edges)];
    }

    /** note_id => similarity, best first, excluding $noteId. */
    protected function semanticNeighbours(User $user, int $noteId): Collection
    {
        $vectors = $this->noteVectors($user);
        if (! isset($vectors[$noteId])) return collect();
        $min = (float) config('services.ai.related_min_similarity', 0.58);
        $margin = (float) config('services.ai.semantic_relative_margin', 0.12);

        $sims = collect($vectors)->except($noteId)
            ->map(fn ($v) => EmbeddingService::dot($vectors[$noteId], $v))
            ->sortDesc();
        $best = $sims->first() ?? 0;

        return $sims->filter(fn ($s) => $s >= $min && $s >= $best - $margin);
    }

    /** @return array<int, float[]> note_id => normalized mean of its chunk vectors (current embedding model only). */
    public function noteVectors(User $user): array
    {
        $provider = $this->ai->embeddingsForUser($user);
        if (! $provider) return [];

        $sums = [];
        Embedding::query()
            ->where('embeddings.user_id', $user->id)->where('model', $provider->model())
            ->join('note_chunks', 'note_chunks.id', '=', 'embeddings.note_chunk_id')
            ->join('notes', 'notes.id', '=', 'note_chunks.note_id')->whereNull('notes.deleted_at')
            ->select('embeddings.id', 'embeddings.vector_data', 'note_chunks.note_id')
            ->chunkById(500, function ($rows) use (&$sums) {
                foreach ($rows as $r) {
                    $v = $r->vector_data;
                    if (! isset($sums[$r->note_id])) { $sums[$r->note_id] = $v; continue; }
                    foreach ($v as $i => $x) $sums[$r->note_id][$i] = ($sums[$r->note_id][$i] ?? 0) + $x;
                }
            }, 'embeddings.id', 'id');

        return array_map([EmbeddingService::class, 'normalize'], $sums);
    }
}
