<?php

namespace App\Http\Controllers;

use App\Models\AISetting;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIProviderManager;
use App\Services\Knowledge\EmbeddingService;
use App\Services\Knowledge\NoteIndexingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AISettingsController extends Controller
{
    public function edit(Request $request, AIProviderManager $ai)
    {
        $user = $request->user();
        return view('settings.ai', [
            'setting' => $ai->settingsFor($user),
            'providers' => AISetting::PROVIDERS,
            'chunkCount' => \App\Models\NoteChunk::where('user_id', $user->id)->count(),
            'embeddedCount' => \App\Models\Embedding::where('user_id', $user->id)->where('model', (string) $ai->settingsFor($user)->embedding_model)->count(),
        ]);
    }

    public function update(Request $request)
    {
        $setting = $this->fill($request);
        $setting->save();
        return back()->with('status', 'AI settings saved.');
    }

    public function testConnection(Request $request, AIProviderManager $ai)
    {
        try {
            $result = $ai->make($this->fill($request))->testConnection();
        } catch (AIProviderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->withInput()->with($result->ok ? 'status' : 'error', $result->message)->with('models', $result->models);
    }

    public function testEmbeddings(Request $request, AIProviderManager $ai)
    {
        $provider = $ai->makeEmbeddings($this->fill($request));
        if (! $provider) return back()->withInput()->with('error', 'Set an embedding model first.');
        try {
            $dims = count($provider->embed(['hello world'])[0] ?? []);
        } catch (AIProviderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->withInput()->with($dims ? 'status' : 'error', $dims ? "Embeddings working ({$dims} dimensions)." : 'Endpoint returned no vector.');
    }

    /** Embed all of the user's notes that are missing vectors for the current model. */
    public function reindex(Request $request, NoteIndexingService $indexer, EmbeddingService $embeddings)
    {
        if (! $embeddings->enabledFor($request->user())) {
            return back()->with('error', 'Save an embedding model before re-indexing.');
        }
        @set_time_limit(300);
        $n = 0;
        $request->user()->notes()->chunkById(25, function ($notes) use ($indexer, &$n) {
            foreach ($notes as $note) { $indexer->index($note); $n++; }
        });
        return back()->with('status', "Re-indexed {$n} note(s) for meaning-based search.");
    }

    protected function fill(Request $request): AISetting
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(AISetting::PROVIDERS))],
            'base_url' => ['nullable', 'url', 'max:500'],
            'model' => ['required', 'string', 'max:150'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'clear_api_key' => ['sometimes', 'boolean'],
            'embedding_provider' => ['nullable', Rule::in(array_keys(AISetting::PROVIDERS))],
            'embedding_base_url' => ['nullable', 'url', 'max:500'],
            'embedding_model' => ['nullable', 'string', 'max:150'],
            'embedding_api_key' => ['nullable', 'string', 'max:500'],
            'clear_embedding_api_key' => ['sometimes', 'boolean'],
        ]);
        $setting = $request->user()->aiSetting ?? $request->user()->aiSetting()->make();
        $setting->fill(collect($data)->only(['provider', 'base_url', 'model', 'embedding_provider', 'embedding_base_url', 'embedding_model'])->all() + ['embedding_provider' => null, 'embedding_base_url' => null, 'embedding_model' => null]);
        if ($request->boolean('clear_embedding_api_key')) $setting->embedding_api_key_encrypted = null;
        elseif (filled($data['embedding_api_key'] ?? null)) $setting->embedding_api_key_encrypted = $data['embedding_api_key'];
        if ($request->boolean('clear_api_key')) $setting->api_key_encrypted = null;
        elseif (filled($data['api_key'] ?? null)) $setting->api_key_encrypted = $data['api_key'];
        return $setting;
    }
}
