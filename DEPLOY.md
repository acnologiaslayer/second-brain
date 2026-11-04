# Free deployment guide

Total cost: **$0**, no credit card needed for any of these.

| Piece | Service | Free tier notes |
|---|---|---|
| App (Laravel, Docker) | **Render** free web service | Sleeps after 15 min idle, ~1 min cold start on first visit |
| Database (MySQL) | **Aiven** free MySQL | 1 GB storage, no expiry. May power off when idle (power back on from console) |
| Chat AI | **Groq** (or OpenRouter `:free` models) | Free API key, rate limited |
| Embeddings (meaning-based search) | **Google Gemini** API | Free API key, rate limited |

> Why not GitHub Pages? It only serves static files and cannot run PHP or MySQL.
> Why not Render's own database? Render's free Postgres is deleted after 30 days. Aiven's free MySQL does not expire.

## 1. Database: Aiven
1. Sign up at https://aiven.io, then **Create service → MySQL → Free plan**.
2. From the service overview, copy **Host, Port, User (`avnadmin`), Password, Database (`defaultdb`)**.
3. Download the **CA certificate** (you will paste its contents into Render).

## 2. AI keys
- Groq: https://console.groq.com/keys → create key (chat model `llama-3.1-8b-instant`).
- Gemini: https://aistudio.google.com/apikey → create key (embedding model `gemini-embedding-001`).

## 3. App: Render
1. Sign in at https://render.com with GitHub.
2. **New → Blueprint**, then pick this repo. Render reads `render.yaml`.
3. Fill the prompted secrets:
   - `APP_KEY`: run `php artisan key:generate --show` locally and paste it
   - `APP_URL`: `https://<your-service>.onrender.com`
   - `DB_HOST`, `DB_PORT`, `DB_PASSWORD`: from Aiven
   - `DB_SSL_CA_PEM`: paste the full contents of Aiven's `ca.pem`
   - `AI_API_KEY`: Groq key
   - `AI_EMBEDDING_API_KEY`: Gemini key
4. Deploy. Migrations run automatically on boot. Health check: `/up`.

After the first deploy, register an account. Semantic search is on by default with the env settings above.
If you add notes before the embedding key is set, open **AI Settings → Re-index all notes**.

## Tips for the demo
- Open the site a minute before presenting so the free instance has woken up.
- Free AI tiers are rate limited. Errors appear as a red message in chat and the rest of the app keeps working.
- To avoid cold starts entirely, run locally with Ollama: `ollama pull llama3.2 && ollama pull nomic-embed-text`, then set the embedding model to `nomic-embed-text` in AI Settings.

## Alternatives
- **Koyeb**: always-on free instance but requires a card for verification. The same `Dockerfile` works.
- **GitHub Codespaces**: free monthly hours. Run `php artisan serve` inside it for a temporary demo URL.
