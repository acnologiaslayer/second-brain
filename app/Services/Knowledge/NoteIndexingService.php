<?php

namespace App\Services\Knowledge;

use App\Models\Note;

/** Splits notes into overlapping chunks used for retrieval. Re-runs on every content change. */
class NoteIndexingService
{
    public function __construct(protected int $chunkSize = 800, protected int $overlap = 100) {}

    public function index(Note $note): void
    {
        $text = trim($note->title."\n\n".$note->content);
        $chunks = $this->split($text);

        $note->chunks()->delete();
        foreach ($chunks as $i => $chunk) {
            $note->chunks()->create([
                'user_id' => $note->user_id,
                'chunk_index' => $i,
                'content' => $chunk,
                'content_hash' => hash('sha256', $chunk),
            ]);
        }
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
