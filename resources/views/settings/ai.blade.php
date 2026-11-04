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
            <div class="flex justify-end gap-2">
                <button formaction="{{ route('settings.ai.test') }}" formmethod="POST" name="_method" value="POST" class="px-4 py-2 border rounded-md text-sm">Test connection</button>
                <x-primary-button>Save</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
