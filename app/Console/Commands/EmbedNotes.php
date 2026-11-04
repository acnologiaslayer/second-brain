<?php

namespace App\Console\Commands;

use App\Models\Note;
use App\Services\Knowledge\NoteIndexingService;
use Illuminate\Console\Command;

class EmbedNotes extends Command
{
    protected $signature = 'brain:embed {--user= : Only this user id}';

    protected $description = 'Chunk and embed all notes that are missing vectors (e.g. after enabling semantic search or changing model)';

    public function handle(NoteIndexingService $indexer): int
    {
        $q = Note::query()->with('user')->when($this->option('user'), fn ($q, $u) => $q->where('user_id', $u));
        $bar = $this->output->createProgressBar($q->count());
        $q->chunkById(50, function ($notes) use ($indexer, $bar) {
            foreach ($notes as $note) { $indexer->index($note); $bar->advance(); }
        });
        $bar->finish();
        $this->newLine();
        $this->info('Done.');
        return self::SUCCESS;
    }
}
