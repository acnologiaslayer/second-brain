# Test Plan and Results

Run all tests: `php artisan test` (Pest, in-memory SQLite, AI calls faked with `Http::fake`).
They also run on every push via GitHub Actions (`.github/workflows/tests.yml`).

**Result: 45 tests, 138 assertions, all passing.**

AI providers are faked in automated tests so results are deterministic and require no API keys.
The semantic tests use a small fake embedding model that places synonyms ("car", "vehicle",
"automobile") close together, which lets us prove meaning-based matching works without a real model.
Real-model behaviour was verified manually (section 3).

## 1. Requirement coverage

| Proposal requirement / success criterion | Automated test(s) | File |
|---|---|---|
| Register, log in, manage own workspace | new users can register; users can authenticate; profile tests | `tests/Feature/Auth/*`, `ProfileTest.php` |
| Notes CRUD with soft delete and restore | user can create, view, update and trash a note | `SecondBrainTest.php` |
| Categories, tags, favorites, pinned | covered in CRUD test (tags synced) and access test (category ownership) | `SecondBrainTest.php` |
| Keyword search and filtering | search finds notes by keyword | `SecondBrainTest.php` |
| **User A cannot access User B's records** | user A cannot access user B records (view, update, delete, category, conversation, message, foreign category id) | `SecondBrainTest.php` |
| Brain AI answers using retrieved notes with sources | brain AI answers with retrieved note sources and never leaks other users notes | `SecondBrainTest.php` |
| AI endpoint unavailable is handled | provider failure is shown as an error message, not a crash | `SecondBrainTest.php` |
| Save AI output only after review | save to brain prefills note form for review | `SecondBrainTest.php` |
| Secure handling of AI credentials | api key is stored encrypted and never rendered | `SecondBrainTest.php` |
| Note links, no self-links | notes cannot link to themselves; already linked notes are not suggested again | `SecondBrainTest.php`, `KnowledgeGraphTest.php` |
| Semantic search (extension) | search finds notes by meaning even without shared keywords; brain AI uses semantically retrieved notes | `SemanticSearchTest.php` |
| Re-index only outdated embeddings | notes are embedded on save and re-embedded only when changed; reindex embeds older notes | `SemanticSearchTest.php` |
| Semantic search privacy | semantic search never returns other users notes | `SemanticSearchTest.php` |
| Degraded mode | embedding outage falls back to keyword search | `SemanticSearchTest.php` |
| AI-suggested links (extension) | suggests semantically related notes with a reason; falls back to shared tags without embeddings | `KnowledgeGraphTest.php` |
| Knowledge graph (extension) | graph contains link, tag and semantic edges; graph never includes other users notes | `KnowledgeGraphTest.php` |
| Demo data | demo seeder builds a usable knowledge base (idempotent, works without AI) | `KnowledgeGraphTest.php` |

## 2. Manual acceptance checklist (end-to-end with a real model)

Environment: Ollama with `qwen2.5:3b` (chat) and `nomic-embed-text` (embeddings), MySQL 8 via Docker, Chrome.

| # | Step | Expected | Result |
|---|---|---|---|
| 1 | Register a new account | Redirect to empty dashboard | Pass |
| 2 | Create note with category and tags | Note page shows category, tags, record number | Pass |
| 3 | Quick capture from dashboard | Note appears in inbox, inbox banner shown | Pass |
| 4 | Search "automobile" (no note contains the word) | "Car service reminder" returned, badge "≈ meaning" | Pass |
| 5 | Search nonsense ("ola") | No results (no false positives) | Pass |
| 6 | Ask Brain AI "What are the normal forms in databases?" | Answer cites "Database normalization" as a source | Pass |
| 7 | Click "Save to notes" on an answer | Note form opens prefilled with a review warning | Pass |
| 8 | Stop Ollama, ask a question | Red ERR message with link to settings; notes still usable | Pass |
| 9 | Open a note, check "Suggested links" | Related notes listed with reasons; "+ link" creates link | Pass |
| 10 | Open Graph | Notes clustered by topic; links solid, similar-meaning dashed orange | Pass |
| 11 | Log in as a second user, open first user's note URL | 403 Forbidden | Pass |
| 12 | Production Docker image with MySQL | Migrations run, all pages 200 | Pass |
| 13 | Mobile width (390px) | Sidebar collapses to a menu, layouts stack | Pass |

## 3. Retrieval quality (real model, demo dataset)

Measured with `nomic-embed-text` on the 17-note demo dataset:

| Query | Top result | Correct? |
|---|---|---|
| automobile | Car service reminder | Yes |
| how to cope with exams | Exam stress tips | Yes |
| normal forms | Database normalization | Yes |
| what happens when a request hits laravel | Laravel request lifecycle | Yes |
| ola (nonsense) | (none) | Yes |

Observation that shaped the design: with this model, unrelated notes still score about 0.3 to 0.45 and
truly related notes about 0.55 to 0.75. A single fixed threshold was unreliable, so retrieval uses an
absolute floor plus a margin relative to the best match, and the graph uses top-k neighbours per note.
