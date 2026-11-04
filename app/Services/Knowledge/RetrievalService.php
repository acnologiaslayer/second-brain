<?php

namespace App\Services\Knowledge;

use App\Models\NoteChunk;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Hybrid retrieval over note chunks, always scoped to the owner.
 *  - Keyword: distinct query-term hits (title hits weighted higher).
 *  - Semantic: cosine similarity of embeddings (when an embedding model is configured).
 * Results are merged with Reciprocal Rank Fusion so neither score scale dominates.
 */
class RetrievalService
{
    protected const STOPWORDS = ['the','a','an','and','or','of','to','in','on','for','is','are','was','what','how','why','do','does','did','i','my','me','you','it','that','this','with','about','can','from','be','by','at','as','which','who','when','where','tell'];
    protected const RRF_K = 60;

    public function __construct(protected EmbeddingService $embeddings) {}

    /** @return Collection<int, array{chunk: NoteChunk, score: float, method: string}> one per note, best first */
    public function retrieve(User $user, string $query, int $limit = 5): Collection
    {
        $keyword = $this->keyword($user, $query); // chunk_id => score, ranked
        $semantic = $this->embeddings->search($user, $query)->pluck('similarity', 'chunk_id');

        $fused = [];
        foreach ([$keyword, $semantic] as $source => $ranked) {
            foreach ($ranked->keys()->values() as $rank => $chunkId) {
                $fused[$chunkId]['score'] = ($fused[$chunkId]['score'] ?? 0) + 1 / (self::RRF_K + $rank + 1);
                $fused[$chunkId]['methods'][] = $source === 0 ? 'keyword' : 'semantic';
            }
        }
        if (! $fused) return collect();

        $chunks = NoteChunk::with('note:id,title,user_id')->whereIn('id', array_keys($fused))->where('user_id', $user->id)->get()->keyBy('id');

        return collect($fused)
            ->filter(fn ($v, $id) => $chunks->has($id) && $chunks[$id]->note)
            ->map(fn ($v, $id) => [
                'chunk' => $chunks[$id],
                'score' => round($v['score'] * 1000, 2),
                'method' => count($v['methods']) > 1 ? 'hybrid' : $v['methods'][0],
            ])
            ->sortByDesc('score')
            ->unique(fn ($r) => $r['chunk']->note_id)
            ->take($limit)
            ->values();
    }

    /** Note IDs ranked by relevance, for the search page. */
    public function rankNoteIds(User $user, string $query, int $limit = 50): Collection
    {
        return $this->retrieve($user, $query, $limit)->mapWithKeys(fn ($r) => [$r['chunk']->note_id => $r['method']]);
    }

    /** @return Collection<int, float> chunk_id => score, best first */
    protected function keyword(User $user, string $query): Collection
    {
        $terms = $this->terms($query);
        if ($terms->isEmpty()) return collect();

        return NoteChunk::query()
            ->where('user_id', $user->id)
            ->whereHas('note')
            ->where(function ($q) use ($terms) {
                foreach ($terms as $t) $q->orWhere('content', 'like', "%{$t}%");
            })
            ->with('note:id,title')
            ->limit(200)
            ->get()
            ->mapWithKeys(function (NoteChunk $c) use ($terms) {
                $text = Str::lower($c->content);
                $title = Str::lower($c->note->title);
                $score = 0.0;
                foreach ($terms as $t) {
                    $hits = substr_count($text, $t);
                    if ($hits) $score += 1 + log($hits);
                    if (str_contains($title, $t)) $score += 2;
                }
                return [$c->id => $score];
            })
            ->filter(fn ($s) => $s > 0)
            ->sortDesc();
    }

    protected function terms(string $query): Collection
    {
        return collect(preg_split('/[^\p{L}\p{N}]+/u', Str::lower($query)))
            ->filter(fn ($t) => mb_strlen($t) > 2 && ! in_array($t, self::STOPWORDS, true))
            ->unique()
            ->take(12)
            ->values();
    }
}
