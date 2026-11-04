<?php

namespace App\Services\Knowledge;

use App\Models\Embedding;
use App\Models\Note;
use App\Models\User;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Stores one L2-normalized vector per note chunk and ranks chunks by cosine similarity.
 * Vectors live in MySQL as JSON; similarity is computed in PHP, which is plenty for
 * a personal knowledge base (thousands of chunks). Swap for pgvector/Qdrant at scale.
 */
class EmbeddingService
{
    public function __construct(protected AIProviderManager $ai) {}

    public function enabledFor(User $user): bool
    {
        return $this->ai->embeddingsForUser($user) !== null;
    }

    /** Embed all chunks of a note that do not yet have a vector for the current model. Never throws. */
    public function embedNote(Note $note): int
    {
        $provider = $this->ai->embeddingsForUser($note->user);
        if (! $provider) return 0;

        $chunks = $note->chunks()->whereDoesntHave('embeddings', fn ($q) => $q->where('model', $provider->model()))->get();
        if ($chunks->isEmpty()) return 0;

        try {
            $vectors = $provider->embed($chunks->pluck('content')->all());
        } catch (AIProviderException $e) {
            Log::warning('Embedding failed for note '.$note->id.': '.$e->getMessage());
            return 0;
        }

        foreach ($chunks->values() as $i => $chunk) {
            if (empty($vectors[$i])) continue;
            $v = self::normalize($vectors[$i]);
            Embedding::updateOrCreate(
                ['note_chunk_id' => $chunk->id, 'model' => $provider->model()],
                ['user_id' => $note->user_id, 'dimensions' => count($v), 'vector_data' => $v],
            );
        }
        return $chunks->count();
    }

    /**
     * @return Collection<int, array{chunk_id: int, note_id: int, similarity: float}> best first
     */
    public function search(User $user, string $query, int $limit = 20, ?float $minSimilarity = null): Collection
    {
        $provider = $this->ai->embeddingsForUser($user);
        if (! $provider || trim($query) === '') return collect();
        $minSimilarity ??= (float) config('services.ai.semantic_min_similarity', 0.45);
        $margin = (float) config('services.ai.semantic_relative_margin', 0.12);

        try {
            $q = self::normalize($provider->embed([$query])[0] ?? []);
        } catch (AIProviderException $e) {
            Log::warning('Query embedding failed: '.$e->getMessage());
            return collect();
        }
        if ($q === []) return collect();

        $results = collect();
        Embedding::query()
            ->where('embeddings.user_id', $user->id)
            ->where('model', $provider->model())
            ->join('note_chunks', 'note_chunks.id', '=', 'embeddings.note_chunk_id')
            ->join('notes', 'notes.id', '=', 'note_chunks.note_id')
            ->whereNull('notes.deleted_at')
            ->select('embeddings.id', 'embeddings.note_chunk_id', 'embeddings.vector_data', 'note_chunks.note_id')
            ->chunkById(500, function ($rows) use ($q, &$results, $minSimilarity) {
                foreach ($rows as $row) {
                    $sim = self::dot($q, $row->vector_data);
                    if ($sim >= $minSimilarity) {
                        $results->push(['chunk_id' => $row->note_chunk_id, 'note_id' => $row->note_id, 'similarity' => round($sim, 4)]);
                    }
                }
            }, 'embeddings.id', 'id');

        // Embedding models give unrelated text a non-trivial baseline similarity, so besides the
        // absolute floor we drop anything far below the best match (relative cutoff).
        $best = $results->max('similarity') ?? 0;
        return $results->filter(fn ($r) => $r['similarity'] >= $best - $margin)
            ->sortByDesc('similarity')->take($limit)->values();
    }

    public static function normalize(array $v): array
    {
        $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v)));
        return $norm > 0 ? array_map(fn ($x) => $x / $norm, $v) : [];
    }

    public static function dot(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        $s = 0.0;
        for ($i = 0; $i < $n; $i++) $s += $a[$i] * $b[$i];
        return $s;
    }
}
