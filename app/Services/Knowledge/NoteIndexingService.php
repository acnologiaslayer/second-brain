<?php

namespace App\Services\Knowledge;

use App\Models\Note;

/** Splits notes into overlapping chunks used for retrieval. Re-runs on every content change. */
class NoteIndexingService
{
    protected int $chunkSize = 800;
    protected int $overlap = 100;

    public function __construct(protected EmbeddingService $embeddings) {}

    public function index(Note $note): void
    {
        $text = trim($note->title."\n\n".$note->content);
        $chunks = $this->split($text);

        // Keep unchanged chunks (and their embeddings); replace only what changed.
        $existing = $note->chunks()->get()->keyBy('chunk_index');
        foreach ($chunks as $i => $chunk) {
            $hash = hash('sha256', $chunk);
            $old = $existing->pull($i);
            if ($old && $old->content_hash === $hash) continue;
            $old?->delete(); // cascades to its outdated embeddings
            $note->chunks()->create(['user_id' => $note->user_id, 'chunk_index' => $i, 'content' => $chunk, 'content_hash' => $hash]);
        }
        $existing->each->delete();

        $this->embeddings->embedNote($note);
    }

    /** @return string[] */
    public function split(string $text): array
    {
        $paragraphs = preg_split("/\n\s*\n/", $text) ?: [];
        $chunks = [];
        $current = '';
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (mb_strlen($current) + mb_strlen($p) > $this->chunkSize && $current !== '') {
                $chunks[] = trim($current);
                $current = mb_substr($current, -$this->overlap)."\n\n";
            }
            while (mb_strlen($p) > $this->chunkSize) {
                $chunks[] = trim($current.mb_substr($p, 0, $this->chunkSize));
                $p = mb_substr($p, $this->chunkSize - $this->overlap);
                $current = '';
            }
            $current .= $p."\n\n";
        }
        if (trim($current) !== '') $chunks[] = trim($current);
        return $chunks;
    }
}
