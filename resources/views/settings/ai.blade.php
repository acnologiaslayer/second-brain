<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">AI Settings</h2></x-slot>
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('settings.ai.update') }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4"
              x-data="{ provider: '{{ old('provider', $setting->provider) }}', defaults: @js(collect($providers)->map->base_url) }">
            @csrf @method('PUT')
            <div>
                <x-input-label for="provider" value="Provider" />
                <select id="provider" name="provider" x-model="provider" class="w-full mt-1 rounded-md border-gray-300">
                    @foreach ($providers as $key => $p)<option value="{{ $key }}">{{ $p['label'] }}</option>@endforeach
                </select>
            </div>
            <div>
                <x-input-label for="base_url" value="Base URL" />
                <x-text-input id="base_url" name="base_url" class="w-full mt-1" :value="old('base_url', $setting->base_url)" x-bind:placeholder="defaults[provider]" />
                <p class="text-xs text-gray-500 mt-1">Leave blank to use the default for the selected provider. For OpenRouter use https://openrouter.ai/api/v1, for Groq https://api.groq.com/openai/v1.</p>
                <x-input-error :messages="$errors->get('base_url')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="model" value="Model" />
                <x-text-input id="model" name="model" class="w-full mt-1" :value="old('model', $setting->model)" required list="models" placeholder="e.g. llama3.2, gpt-4o-mini" />
                @if (session('models'))<datalist id="models">@foreach (session('models') as $m)<option value="{{ $m }}">@endforeach</datalist>
                    <p class="text-xs text-gray-500 mt-1">Available: {{ implode(', ', array_slice(session('models'), 0, 15)) }}</p>@endif
                <x-input-error :messages="$errors->get('model')" class="mt-1" />
            </div>
            <div x-show="provider !== 'ollama'">
                <x-input-label for="api_key" value="API key" />
                <x-text-input id="api_key" name="api_key" type="password" class="w-full mt-1" autocomplete="off" placeholder="{{ $setting->hasApiKey() ? '•••••••• (saved, leave blank to keep)' : 'Not set' }}" />
                @if ($setting->hasApiKey())<label class="text-xs flex items-center gap-1 mt-1"><input type="checkbox" name="clear_api_key" value="1" class="rounded"> Remove saved key</label>@endif
                <p class="text-xs text-gray-500 mt-1">Stored encrypted. Never shown again after saving.</p>
            </div>
            <div class="flex justify-end">
                <button formaction="{{ route('settings.ai.test') }}" formmethod="POST" name="_method" value="POST" class="px-4 py-2 border rounded-md text-sm">Test chat connection</button>
            </div>

            <hr>
            <div x-data="{ eprov: '{{ old('embedding_provider', $setting->embedding_provider) }}' }" class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-800">🧠 Meaning-based search (embeddings)</h3>
                    <p class="text-xs text-gray-500">Optional. Finds notes by meaning, not just exact words (e.g. "car" finds a note about "vehicles"). Leave the model empty to use keyword search only.
                        Free options: Ollama <code>nomic-embed-text</code>, or Google Gemini (base URL <code>https://generativelanguage.googleapis.com/v1beta/openai</code>, model <code>gemini-embedding-001</code>).</p>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="embedding_provider" value="Embedding provider" />
                        <select id="embedding_provider" name="embedding_provider" x-model="eprov" class="w-full mt-1 rounded-md border-gray-300">
                            <option value="">Same as chat provider</option>
                            @foreach ($providers as $key => $p)<option value="{{ $key }}">{{ $p['label'] }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="embedding_model" value="Embedding model" />
                        <x-text-input id="embedding_model" name="embedding_model" class="w-full mt-1" :value="old('embedding_model', $setting->embedding_model)" placeholder="e.g. nomic-embed-text" />
                    </div>
                </div>
                <div x-show="eprov !== ''">
                    <x-input-label for="embedding_base_url" value="Embedding base URL" />
                    <x-text-input id="embedding_base_url" name="embedding_base_url" class="w-full mt-1" :value="old('embedding_base_url', $setting->embedding_base_url)" x-bind:placeholder="defaults[eprov] ?? ''" />
                    <x-input-error :messages="$errors->get('embedding_base_url')" class="mt-1" />
                </div>
                <div x-show="eprov !== '' && eprov !== 'ollama'">
                    <x-input-label for="embedding_api_key" value="Embedding API key" />
                    <x-text-input id="embedding_api_key" name="embedding_api_key" type="password" class="w-full mt-1" autocomplete="off" placeholder="{{ $setting->hasEmbeddingApiKey() ? '•••••••• (saved, leave blank to keep)' : 'Not set' }}" />
                    @if ($setting->hasEmbeddingApiKey())<label class="text-xs flex items-center gap-1 mt-1"><input type="checkbox" name="clear_embedding_api_key" value="1" class="rounded"> Remove saved key</label>@endif
                </div>
                @if ($setting->embedding_model)
                    <p class="text-xs text-gray-600">Index status: {{ $embeddedCount }} / {{ $chunkCount }} note chunks embedded with <code>{{ $setting->embedding_model }}</code>.</p>
                @endif
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <button formaction="{{ route('settings.ai.test-embeddings') }}" formmethod="POST" name="_method" value="POST" class="px-4 py-2 border rounded-md text-sm">Test embeddings</button>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
        @if ($setting->exists && $setting->embedding_model)
            <form method="POST" action="{{ route('settings.ai.reindex') }}" class="mt-4 text-right">
                @csrf
                <button class="px-4 py-2 rounded-md text-sm bg-purple-600 text-white hover:bg-purple-700">Re-index all notes for meaning-based search</button>
            </form>
        @endif
    </div>
</x-app-layout>
