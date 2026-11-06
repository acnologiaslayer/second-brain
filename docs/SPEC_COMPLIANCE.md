# Specification Compliance Matrix

Checked against **Second Brain: Technical Implementation Specification** (sections 1 to 18).
Status: **Met** = implemented and tested, **Met (variant)** = requirement met with a documented
implementation choice that differs from the spec's suggestion, **Partial** = see note.

Automated evidence: `php artisan test` (65 tests). Acceptance criteria live in
`tests/Feature/AcceptanceCriteriaTest.php`, one test per AC.

## 13. Acceptance criteria

| ID | Scenario | Status | Test |
|---|---|---|---|
| AC-01 | Register and log in | Met | `AC-01 register and log in` |
| AC-02 | Create/edit/delete note | Met | `AC-02 create, edit, delete note persists and shows in list` |
| AC-03 | Organize note (category + multiple tags) | Met | `AC-03 category and multiple tags can be assigned` |
| AC-04 | Search within current user's data | Met | `AC-04 search returns matches only from the current user` |
| AC-05 | Cross-user access blocked | Met | `AC-05 ...`, plus `user A cannot access user B records` |
| AC-06 | AI chat returns response or actionable error | Met | `AC-06 configured provider returns a response`, `AC-11` |
| AC-07 | Relevant current-user notes supplied as context | Met | `AC-07 relevant current-user notes are supplied as context, separate from system rules` |
| AC-08 | Response displays note references | Met | `AC-08 response stores and displays note and chunk references` |
| AC-09 | Proposed note previewed, saved only after confirmation | Met | `AC-09 AI output is proposed, previewed and saved only after confirmation` (+ fallback test) |
| AC-10 | Provider can be tested and selected | Met | `AC-10 provider can be configured, tested and selected` |
| AC-11 | Provider failure shows error, keeps user message | Met | `AC-11 provider failure shows an error and retains the users message...` |
| AC-12 | Changed content re-indexed, stale data not used | Met | `AC-12 changed content is re-indexed and stale retrieval data is not used` |

## Sections 1 to 12

| § | Requirement | Status | Where / note |
|---|---|---|---|
| 1 | Provider code behind a common interface | Met | `AIProviderInterface`, `AIProviderManager` |
| 1 | Every private query scoped to the user | Met | policies + `user_id` scoping; AC-05, AC-04, graph/semantic isolation tests |
| 1 | AI output untrusted, confirm writes | Met | §9 flow below |
| 1 | Retrieval explainable (record + show sources) | Met | `messages.metadata.sources` (note_id, chunk_id, method, score) |
| 2 | MVC + service layer, no prompts in controllers | Met | `app/Services/Knowledge`, `app/Services/AI` |
| 3 | Directory structure | Met (variant) | Same layout. Single `NoteRequest` instead of separate Store/Update requests (identical rules). Added `CaptureController`, `GraphController`, `RelatedNotesService`, `EmbeddingService`, `NoteProposalService` |
| 4.1 to 4.6 | Schema: users, notes, categories, tags, note_tag, conversations, messages, note_chunks, embeddings, note_links, ai_settings | Met | `database/migrations/2026_11_05_*` |
| 4 | Indexes, unique pivot, FK delete rules, no orphans | Met | composite PK on note_tag; cascades; `null on delete` for category; AC-12 checks no orphan chunks/embeddings |
| 4 | Re-index on change, invalidate embeddings | Met | hash-based incremental chunking; AC-12 |
| 4 | Encrypted secrets, not logged | Met | `encrypted` cast, `$hidden`; keys never rendered (test) |
| 5 | Eloquent relationship map | Met | all relations in `app/Models` |
| 6 | Route plan incl. auth middleware | Met | `routes/web.php`; `save-as-note` is POST as specified; `/tags` GET/POST (+DELETE) |
| 7 | testConnection + generateResponse interface | Met | both adapters |
| 7 | Manager: resolve adapter, validate config, timeouts, auth/rate-limit errors, normalized responses | Met | `AIProviderManager`, adapters map 401/429/5xx to `AIProviderException` |
| 7 | Keys never sent to Blade/JS | Met | test `api key is stored encrypted and never rendered` |
| 8.1 | Chat flow; failure does not corrupt conversation | Met | user message saved first; error turns stored with `status=error` and **excluded from later context** (AC-11) |
| 8.2 | User-scoped chunk search, ranking, **context-size limit** | Met | `RetrievalService` + `PromptBuilder::context()` (char budget, `AI_MAX_CONTEXT_CHARS`); test `spec 8.2/10` |
| 8.2 | Prompt distinguishes evidence vs uncertainty | Met | system rule 2 ("Your notes don't cover this") |
| 8.2 | Store note IDs **and chunk IDs** in metadata | Met | AC-08 |
| 8.3 | Keyword first, semantic later; UI states actual method | Met | hybrid when an embedding model is set, keyword otherwise; chat header shows `retrieval: keyword` or `keyword + semantic`; search placeholder adapts (test `spec 8.3`) |
| 9 | Feed My Brain: AI proposes title, body, **suggested category and tags**, preview, edit, confirm, validate, index | Met | `NoteProposalService` (JSON mode + repair for small models, fuzzy category match), shown in note form with PROPOSED NOTE banner; AC-09 |
| 10 | System instructions separate from user messages and excerpts | Met | rules, excerpts and turns are separate messages (AC-07) |
| 10 | Notes treated as untrusted, bounded context, no other-user data, no secrets in prompts | Met | AC-07, `spec 8.2/10` test |
| 10 | Label AI content, show sources | Met | BRAIN.AI label, source chips, "AI SUMMARY" and "PROPOSED NOTE" banners |
| 11 | Auth, policies, CSRF, validation, XSS escaping | Met | Breeze, policies, CSRF on forms and fetch, Form Requests, Blade escaping + escape-first markdown |
| 11 | **Network: HTTPS for remote endpoints, block internal targets** | Met | `SafeAiEndpoint` rule (https required for remote, private/reserved/metadata IPs blocked, DNS checked, redirects disabled); `AI_ALLOW_LOCAL_ENDPOINTS=false` in `render.yaml`; test `spec 11` |
| 11 | Remove derived data on permanent delete | Met | FK cascade; AC-12 |
| 12 | Login/Register | Met | |
| 12 | Dashboard: stats, recent, quick capture, Brain AI shortcut | Met | |
| 12 | All Notes: search, filters, **sorting**, grid, create | Met | sort: recently updated, newest, least recent, title (test `spec 12: notes can be sorted`) |
| 12 | Note editor: title, content, category, tags, favorite/pin, save/cancel | Met | |
| 12 | **Quick Capture: fast entry, inbox list, convert-to-note** | Met | `/capture` (Inbox in sidebar); test `spec 12: quick capture page...` |
| 12 | Brain AI: sidebar, thread, composer, **provider/model indicator**, citations, save | Met | header badge `OLLAMA (LOCAL) · QWEN2.5:3B` |
| 12 | AI Settings: provider, endpoint, model, secret, test, save | Met | |
| 12 | Trash: restore, permanent delete | Met | |

## Sections 14 to 18

| § | Item | Status |
|---|---|---|
| 14 | Milestones M1 to M6 | Met (all six) |
| 15 | MVP boundary | Met, plus extensions: semantic search, provider switching, AI-suggested links, knowledge graph |
| 16 | Demonstration script | Met. Verified end-to-end in a browser with the real local model (see below) |
| 17 | MySQL primary database | Met: production Docker image and deploy target use MySQL. The full test suite (65 tests) passes on MySQL 8 and runs on both MySQL and SQLite in CI. SQLite is only the zero-setup default for local dev |
| 17 | Verify model chat + embedding capability first | Met: both checked with "Test connection" / "Test embeddings" |
| 18 | Conventional Laravel first, AI via manager, keyword before vectors | Met |

### Not implemented (explicitly optional in the spec)
- **Streaming responses** and **tool calling** (§7, §9): optional. Responses are returned whole; no tool calling, so no `propose_note` tool is needed (the proposal flow covers the same need with explicit confirmation).
- **File/PDF ingestion and a rich-text editor** (§15 / proposal extensions): not built. Notes are plain text with light formatting in chat answers.
- **Dedicated vector database** (§8.3, §17): vectors are stored as JSON in MySQL and compared in PHP, which suits a personal knowledge base of thousands of chunks. pgvector/Qdrant would be the next step at larger scale.

## Real-model verification of the demo script (§16)

Run in Chrome against the live app with Ollama `qwen2.5:3b` + `nomic-embed-text`:

| Step | Observed |
|---|---|
| Ask "What does 3NF remove?" | Answer: "3NF removes transitive dependencies…" with citation `[#19]` |
| Sources | Chip `#019 Database normalization` under the answer |
| Indicator | `OLLAMA (LOCAL) · QWEN2.5:3B` and `RETRIEVAL: KEYWORD + SEMANTIC` |
| Save to notes | Preview with PROPOSED NOTE banner: title "3NF in Databases", category Databases, tags `database, normalization, 3nf, redundancy`, type Study. Nothing saved yet |
| Confirm | Note created and found by search |
| Quick capture | Item listed in Inbox, "Convert to note" opens the editor |
| Proposal reliability | 5/5 real-model proposals parsed after adding JSON mode + repair (2 of 2 failed before: the model emitted unquoted values) |
