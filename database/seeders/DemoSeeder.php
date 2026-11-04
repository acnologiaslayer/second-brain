<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Knowledge\NoteIndexingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Realistic demo knowledge base for presentations: php artisan db:seed --class=DemoSeeder
 * Login: demo@example.com / password. Safe to re-run (skips notes that already exist).
 */
class DemoSeeder extends Seeder
{
    public function run(NoteIndexingService $indexer): void
    {
        $user = User::firstOrCreate(['email' => 'demo@example.com'], ['name' => 'Demo User', 'password' => Hash::make('password')]);

        $cats = [];
        foreach ([
            'Databases' => 'Relational design, SQL and indexing',
            'Web Development' => 'Laravel, PHP and HTTP',
            'Machine Learning' => 'Models, embeddings and evaluation',
            'Study Skills' => 'Learning and exam techniques',
            'Personal' => 'Life admin',
        ] as $name => $desc) {
            $cats[$name] = $user->categories()->firstOrCreate(['name' => $name], ['description' => $desc]);
        }

        $notes = [
            ['Database normalization', 'Databases', 'study', 'databases, exam', true, false,
             "1NF removes repeating groups so every column holds an atomic value.\n\n2NF removes partial dependencies: every non-key attribute depends on the whole primary key.\n\n3NF removes transitive dependencies: non-key attributes depend only on the key.\n\nNormalization reduces redundancy and prevents insert, update and delete anomalies."],
            ['SQL indexes and query performance', 'Databases', 'study', 'databases, performance', false, false,
             "A B-tree index lets MySQL find rows without a full table scan. Index columns used in WHERE, JOIN and ORDER BY.\n\nComposite indexes follow the leftmost-prefix rule. Too many indexes slow down inserts and updates.\n\nUse EXPLAIN to check whether a query uses an index."],
            ['ACID transactions', 'Databases', 'study', 'databases, exam', false, false,
             "Atomicity: all or nothing. Consistency: constraints hold before and after. Isolation: concurrent transactions do not interfere. Durability: committed data survives crashes.\n\nIsolation levels: read uncommitted, read committed, repeatable read (MySQL default), serializable."],
            ['Entity relationship modelling', 'Databases', 'study', 'databases, design', false, false,
             "Entities become tables, attributes become columns, relationships become foreign keys.\n\nOne-to-many: foreign key on the many side. Many-to-many: a pivot table with two foreign keys, like note_tag in this project."],
            ['Laravel request lifecycle', 'Web Development', 'study', 'laravel, php', false, false,
             "A request enters public/index.php, boots the application, passes through the HTTP kernel and global middleware, gets matched by the router, runs route middleware, and reaches a controller which returns a response."],
            ['Eloquent relationships', 'Web Development', 'study', 'laravel, php, databases', false, false,
             "hasMany / belongsTo for one-to-many, belongsToMany with a pivot table for many-to-many, hasOne for one-to-one.\n\nUse eager loading with with() to avoid the N+1 query problem."],
            ['Laravel authorization with policies', 'Web Development', 'study', 'laravel, security', false, false,
             "Policies group authorization logic per model. Call \$this->authorize('update', \$note) in a controller.\n\nEvery query for private data must be scoped to the authenticated user so one user can never read another user's records."],
            ['Preventing SQL injection and XSS', 'Web Development', 'resource', 'security, php', false, false,
             "Use parameter binding (Eloquent and the query builder do this) instead of concatenating SQL strings.\n\nBlade {{ }} escapes output automatically. Only use {!! !!} for trusted HTML."],
            ['What are embeddings?', 'Machine Learning', 'study', 'ai, embeddings', true, false,
             "An embedding model turns text into a vector of numbers so that texts with similar meaning end up close together.\n\nSimilarity is usually measured with cosine similarity. This is what powers semantic search: a query and a note can match even if they share no words."],
            ['Retrieval-augmented generation (RAG)', 'Machine Learning', 'study', 'ai, embeddings, llm', false, false,
             "RAG retrieves relevant documents and adds them to the prompt so a language model answers from that context instead of only its training data.\n\nSteps: chunk documents, embed chunks, retrieve the closest chunks for a question, build a prompt, generate an answer and cite sources."],
            ['Overfitting and regularization', 'Machine Learning', 'study', 'ai, exam', false, false,
             "Overfitting: the model memorizes training data and performs poorly on new data. Signs: training accuracy high, validation accuracy low.\n\nFixes: more data, simpler model, dropout, L2 regularization, early stopping, cross-validation."],
            ['Pomodoro technique', 'Study Skills', 'idea', 'productivity, exam', false, false,
             "Work for 25 minutes, break for 5. After four rounds take a longer 20 minute break.\n\nIt helps with procrastination because starting a 25 minute block feels manageable."],
            ['Spaced repetition and active recall', 'Study Skills', 'study', 'productivity, exam', false, true,
             "Testing yourself (active recall) beats re-reading. Review material at increasing intervals: 1 day, 3 days, 1 week, 2 weeks.\n\nFlashcard apps like Anki schedule reviews automatically."],
            ['Exam stress tips', 'Study Skills', 'idea', 'wellbeing, exam', false, false,
             "Sleep 8 hours before the exam. Take short breaks. Eat properly. Do past papers under timed conditions so the real exam feels familiar."],
            ['Car service reminder', 'Personal', 'text', 'personal', false, false,
             "Change engine oil and check tyre pressure every 5000 km. Next service due in December."],
            ['Project idea: Second Brain', 'Web Development', 'idea', 'laravel, ai, project', true, true,
             "A Laravel knowledge base with an AI assistant that answers from my own notes using retrieval-augmented generation and semantic search over embeddings."],
        ];

        $created = [];
        foreach ($notes as [$title, $cat, $type, $tags, $fav, $pin, $content]) {
            $note = $user->notes()->where('title', $title)->first();
            if (! $note) {
                $note = $user->notes()->create(['title' => $title, 'content' => $content, 'note_type' => $type, 'category_id' => $cats[$cat]->id, 'is_favorite' => $fav, 'is_pinned' => $pin]);
            } else {
                $note->update(['category_id' => $cats[$cat]->id, 'content' => $content, 'note_type' => $type]);
            }
            $note->tags()->sync(collect(explode(',', $tags))->map(fn ($t) => $user->tags()->firstOrCreate(['name' => trim($t)])->id));
            $indexer->index($note);
            $created[$title] = $note;
        }

        foreach ([
            ['Retrieval-augmented generation (RAG)', 'What are embeddings?', 'builds on'],
            ['Project idea: Second Brain', 'Retrieval-augmented generation (RAG)', 'uses'],
            ['Eloquent relationships', 'Entity relationship modelling', 'implements'],
            ['Spaced repetition and active recall', 'Pomodoro technique', 'pairs with'],
        ] as [$from, $to, $label]) {
            $created[$from]->outgoingLinks()->firstOrCreate(['target_note_id' => $created[$to]->id], ['label' => $label, 'user_id' => $user->id]);
        }

        $user->notes()->where('title', 'Remember to buy a new charger')->exists() || $user->notes()->create(['title' => 'Remember to buy a new charger', 'content' => 'Remember to buy a new charger', 'note_type' => 'inbox']);
    }
}
