<x-app-layout>
    <x-slot name="title">AI Settings</x-slot>
    <div class="max-w-3xl mx-auto space-y-6"
         x-data="{ provider: '{{ old('provider', $setting->provider) }}', eprov: '{{ old('embedding_provider', $setting->embedding_provider) }}', defaults: @js(collect($providers)->map->base_url) }">
        <div>
            <div class="eyebrow">System</div>
            <h1 class="page-title mt-1">AI Settings</h1>
            <p class="text-sm text-zinc-500 mt-1">Connect Brain AI to a local model or any OpenAI-compatible API. Keys are encrypted and never shown again.</p>
        </div>

        <form method="POST" action="{{ route('settings.ai.update') }}" class="space-y-6">
            @csrf @method('PUT')

            <section class="card">
                <div class="px-5 py-4 border-b border-zinc-200 flex items-center gap-3">
                    <span class="font-mono text-[11px] font-bold text-brand-500">01</span>
                    <div><h2 class="font-semibold text-zinc-900">Chat model</h2><p class="text-xs text-zinc-500">Used for Brain AI answers and summaries.</p></div>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <span class="label">Provider</span>
                        <div class="grid sm:grid-cols-3 gap-2">
                            @foreach ($providers as $key => $p)
                                <label class="cursor-pointer">
                                    <input type="radio" name="provider" value="{{ $key }}" x-model="provider" class="sr-only peer">
                                    <span class="flex items-center gap-2 border border-zinc-300 bg-paper px-3 py-2.5 text-sm text-zinc-700 peer-checked:border-zinc-900 peer-checked:shadow-[2px_2px_0_0_#f2650f] peer-checked:text-zinc-900 hover:border-zinc-500 transition">{{ $p['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label" for="base_url">Base URL</label>
                            <input id="base_url" name="base_url" class="input" value="{{ old('base_url', $setting->base_url) }}" x-bind:placeholder="defaults[provider]">
                            @error('base_url')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="model">Model</label>
                            <input id="model" name="model" class="input" value="{{ old('model', $setting->model) }}" required list="models" placeholder="e.g. llama3.2">
                            @if (session('models'))<datalist id="models">@foreach (session('models') as $m)<option value="{{ $m }}">@endforeach</datalist>@endif
                            @error('model')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    @if (session('models'))
                        <div class="flex flex-wrap gap-1.5">@foreach (array_slice(session('models'), 0, 20) as $m)
                            <button type="button" @click="document.getElementById('model').value = '{{ $m }}'" class="badge border-zinc-300 text-zinc-600 normal-case tracking-normal hover:border-zinc-900 hover:text-brand-600">{{ $m }}</button>
                        @endforeach</div>
                    @endif
                    <div x-show="provider !== 'ollama'" x-cloak>
                        <label class="label" for="api_key">API key</label>
                        <input id="api_key" name="api_key" type="password" class="input" autocomplete="off" placeholder="{{ $setting->hasApiKey() ? '•••••••• saved (leave blank to keep)' : 'Not set' }}">
                        @if ($setting->hasApiKey())<label class="mt-2 flex items-center gap-2 text-xs text-zinc-500"><input type="checkbox" name="clear_api_key" value="1" class="rounded-none border-zinc-400 text-brand-500"> Remove saved key</label>@endif
                    </div>
                    <p class="hint">Free cloud options: Groq (<code class="font-mono text-zinc-700">https://api.groq.com/openai/v1</code>) or OpenRouter (<code class="font-mono text-zinc-700">https://openrouter.ai/api/v1</code>).</p>
                </div>
                <div class="px-5 py-3 bg-zinc-100/60 border-t border-zinc-200 flex justify-end">
                    <button formaction="{{ route('settings.ai.test') }}" formmethod="POST" name="_method" value="POST" class="btn-secondary">Test connection</button>
                </div>
            </section>

            <section class="card">
                <div class="px-5 py-4 border-b border-zinc-200 flex items-center gap-3">
                    <span class="font-mono text-[11px] font-bold text-brand-500">02</span>
                    <div class="flex-1"><h2 class="font-semibold text-zinc-900">Meaning-based search</h2><p class="text-xs text-zinc-500">Optional embedding model. Finds notes by meaning, not just exact words.</p></div>
                    @if ($setting->embedding_model)
                        <span class="badge border-zinc-300 text-zinc-600 gap-1.5"><span class="led {{ $chunkCount && $embeddedCount >= $chunkCount ? 'bg-ok' : 'bg-warn' }}"></span>{{ $embeddedCount }}/{{ $chunkCount }} indexed</span>
                    @else
                        <span class="badge border-zinc-300 text-zinc-500 gap-1.5"><span class="led bg-zinc-300"></span>off</span>
                    @endif
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label" for="embedding_provider">Provider</label>
                            <select id="embedding_provider" name="embedding_provider" x-model="eprov" class="input">
                                <option value="">Same as chat</option>
                                @foreach ($providers as $key => $p)<option value="{{ $key }}">{{ $p['label'] }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="embedding_model">Embedding model</label>
                            <input id="embedding_model" name="embedding_model" class="input" value="{{ old('embedding_model', $setting->embedding_model) }}" placeholder="e.g. nomic-embed-text">
                        </div>
                    </div>
                    <div x-show="eprov !== ''" x-cloak>
                        <label class="label" for="embedding_base_url">Base URL</label>
                        <input id="embedding_base_url" name="embedding_base_url" class="input" value="{{ old('embedding_base_url', $setting->embedding_base_url) }}" x-bind:placeholder="defaults[eprov] ?? ''">
                        @error('embedding_base_url')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="eprov !== '' && eprov !== 'ollama'" x-cloak>
                        <label class="label" for="embedding_api_key">API key</label>
                        <input id="embedding_api_key" name="embedding_api_key" type="password" class="input" autocomplete="off" placeholder="{{ $setting->hasEmbeddingApiKey() ? '•••••••• saved (leave blank to keep)' : 'Not set' }}">
                        @if ($setting->hasEmbeddingApiKey())<label class="mt-2 flex items-center gap-2 text-xs text-zinc-500"><input type="checkbox" name="clear_embedding_api_key" value="1" class="rounded-none border-zinc-400 text-brand-500"> Remove saved key</label>@endif
                    </div>
                    <p class="hint">Free options: Ollama <code class="font-mono text-zinc-700">nomic-embed-text</code>, or Gemini (<code class="font-mono text-zinc-700">https://generativelanguage.googleapis.com/v1beta/openai</code>, model <code class="font-mono text-zinc-700">gemini-embedding-001</code>). Leave the model empty for keyword search only.</p>
                </div>
                <div class="px-5 py-3 bg-zinc-100/60 border-t border-zinc-200 flex flex-wrap justify-end gap-2">
                    <button formaction="{{ route('settings.ai.test-embeddings') }}" formmethod="POST" name="_method" value="POST" class="btn-secondary">Test embeddings</button>
                    @if ($setting->exists && $setting->embedding_model)
                        <button formaction="{{ route('settings.ai.reindex') }}" formmethod="POST" name="_method" value="POST" class="btn-secondary" onclick="this.textContent='Re-indexing…'">Re-index notes</button>
                    @endif
                </div>
            </section>

            <div class="flex justify-end"><button class="btn-primary px-6">Save settings</button></div>
        </form>
    </div>
</x-app-layout>
