# 🧠 Second Brain

An AI-powered personal knowledge management system built with **Laravel, Blade, Tailwind and MySQL**.
Capture, organize, search and connect notes, then chat with **Brain AI**, an assistant that answers using your own notes and cites the sources it used.

## Features
- Authentication (Laravel Breeze) with strict per-user data isolation (policies on every resource)
- Notes CRUD with categories, tags, favorites, pinning, soft-delete trash and restore
- Quick Capture inbox, keyword search and filters (category, tag, type, favorites)
- Note-to-note links (bi-directional view)
- **Brain AI** chat with saved conversation history
  - Retrieval over note chunks, scoped to the current user
  - Source references shown under each answer
  - "Save to Brain": AI replies open in the note form for review before saving
  - One-click AI note summaries
- Provider-independent AI layer: **Ollama**, **LM Studio**, or any **OpenAI-compatible** API (OpenAI, OpenRouter, Groq...)
- API keys encrypted at rest and never rendered back to the browser

## Architecture
```
app/Services/AI/          AIProviderInterface, AIProviderManager, OllamaProvider, OpenAICompatibleProvider
app/Services/Knowledge/   NoteIndexingService (chunking), RetrievalService, PromptBuilder, NoteSummarizationService
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

### AI provider
- **Ollama (local, free):** install from ollama.com, run `ollama pull llama3.2`, keep defaults.
- **Cloud:** open *AI Settings*, choose "OpenAI-compatible", set base URL, model and API key, then *Test connection*.

## Tests
```bash
php artisan test
```
Covers CRUD, search, cross-user access denial (User A vs User B), retrieval grounding with sources, provider failure handling, save-to-brain review flow and API key encryption.

## Roadmap
- [x] Phase 1-4: foundation, knowledge base, AI chat, brain integration
- [ ] Phase 5: embeddings / semantic search, knowledge graph view
- [ ] Phase 6: final testing, documentation and demo
