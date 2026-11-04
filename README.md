# 🧠 Second Brain

An AI-powered personal knowledge management system built with **Laravel, Blade, Tailwind and MySQL**.
Capture, organize, search and connect notes, then chat with **Brain AI**, an assistant that answers using your own notes and cites the sources it used.

## Features
- Authentication (Laravel Breeze) with strict per-user data isolation (policies on every resource)
- Notes CRUD with categories, tags, favorites, pinning, soft-delete trash and restore
- Quick Capture inbox, keyword search and filters (category, tag, type, favorites)
- Note-to-note links (bi-directional view) and **AI-suggested links** with reasons (similar meaning, shared tags, same category)
- **Knowledge graph**: interactive map of your notes (your links, similar meaning, shared tags)
- **Brain AI** chat with saved conversation history
  - Retrieval over note chunks, scoped to the current user
  - Source references shown under each answer
  - "Save to Brain": AI replies open in the note form for review before saving
  - One-click AI note summaries
- **Meaning-based (semantic) search**: notes are embedded per chunk and combined with keyword search via Reciprocal Rank Fusion. Finds "car maintenance" when you search "automobile". Falls back to keyword search if no embedding model is configured or the endpoint is down.
- Provider-independent AI layer: **Ollama**, **LM Studio**, or any **OpenAI-compatible** API (OpenAI, OpenRouter, Groq...)
- API keys encrypted at rest and never rendered back to the browser

## Architecture
```
app/Services/AI/          AIProviderInterface, AIProviderManager, OllamaProvider, OpenAICompatibleProvider
app/Services/AI/          + EmbeddingProviderInterface, OllamaEmbeddings, OpenAICompatibleEmbeddings
app/Services/Knowledge/   NoteIndexingService (incremental chunking), EmbeddingService (vectors + cosine),
                          RetrievalService (hybrid keyword + semantic, RRF), PromptBuilder, NoteSummarizationService
app/Http/Controllers/     Dashboard, Note, Category, Conversation, Message, AISettings
app/Policies/             Note, Category, Conversation ownership checks
```
Controllers depend only on `AIProviderManager`, never on a vendor API.

## Setup
Requirements: PHP 8.3+, Composer, Node 20+, MySQL 8 (or SQLite for quick local dev).

```bash
git clone https://github.com/acnologiaslayer/second-brain.git
cd second-brain
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
# For MySQL edit DB_CONNECTION=mysql, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
php artisan migrate
php artisan serve
```

### Meaning-based search
Set an embedding model in *AI Settings* (e.g. Ollama `nomic-embed-text`, or Gemini `gemini-embedding-001`), then click *Re-index all notes* or run `php artisan brain:embed`.

### AI provider
- **Ollama (local, free):** install from ollama.com, run `ollama pull llama3.2`, keep defaults.
- **Cloud:** open *AI Settings*, choose "OpenAI-compatible", set base URL, model and API key, then *Test connection*.

## Documentation
- [Architecture, ERD and security](docs/ARCHITECTURE.md)
- [Test plan and results](docs/TESTING.md)
- [User guide and demo script](docs/USER_GUIDE.md)
- [Free deployment](DEPLOY.md)

Demo data: `php artisan db:seed --class=DemoSeeder` (login demo@example.com / password).

## Free hosting
See **[DEPLOY.md](DEPLOY.md)**: Render (app) + Aiven (MySQL) + Groq (chat) + Gemini (embeddings), all free, no credit card.

## Tests
```bash
php artisan test
```
Covers CRUD, search, cross-user access denial (User A vs User B), retrieval grounding with sources, provider failure handling, save-to-brain review flow, API key encryption, and semantic search (synonym matching, re-embedding only changed chunks, user isolation, outage fallback).

## Roadmap
- [x] Phase 1-4: foundation, knowledge base, AI chat, brain integration
- [x] Phase 5: semantic search, AI-suggested links, knowledge graph
- [x] Phase 6: testing, documentation, demo data
