# User Guide and Demo Script

## Quick start (local)
```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder     # optional demo data
php artisan serve
```
Log in with **demo@example.com / password** (after seeding).

AI: install Ollama, then `ollama pull qwen2.5:3b` and `ollama pull nomic-embed-text`.
In **AI Settings**, set the chat model to `qwen2.5:3b` and the embedding model to `nomic-embed-text`,
then click **Re-index notes**.

## Features at a glance
| Area | What you can do |
|---|---|
| Home | See counts, capture a thought with `>_`, jump to pinned and recent notes |
| Notes | Search (Smart = meaning + keyword, Exact = literal), filter by category, tag, type |
| Note page | Favorite, pin, edit, summarize with AI, link notes, accept suggested links |
| Brain AI | Ask questions; answers list the notes they used; save answers as notes after review |
| Graph | See how notes connect: your links (solid), similar meaning (dashed orange), shared tags (grey) |
| Organize | Create, rename and delete categories; browse tags |
| AI Settings | Pick provider and model, test the connection, enable meaning-based search |

Keyboard: `/` focuses search, `Ctrl+S` saves a note, `Ctrl+Enter` submits quick capture, `Enter` sends a chat message.

## 5-minute demo script
1. **Problem** (30s): information is scattered; keyword search fails when you remember the meaning but not the words.
2. **Home** (30s): show stats and quick capture a thought. It lands in the inbox.
3. **Organize** (30s): open a note, show category, tags, pin, favorite.
4. **Meaning-based search** (60s): search "automobile". The car note appears although it never uses that word. Search "ola": nothing, no false positives.
5. **Brain AI** (90s): ask "What are the normal forms in databases?". Point at the source chips under the answer. Click one to open the note. Click "Save to notes" and show the review warning.
6. **Suggested links + Graph** (60s): on "Database normalization", show suggested links with reasons; click "+ link". Open Graph and show clusters by topic.
7. **Security** (30s): log in as another user and open the first user's note URL: 403. Mention encrypted API keys and the 45 automated tests.

Tip: open the app and send one chat message before presenting so the local model is already loaded.
