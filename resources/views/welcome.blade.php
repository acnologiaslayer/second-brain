<x-guest-layout>
    <h1 class="text-2xl font-semibold text-zinc-900 tracking-tight">Your personal knowledge base, with an AI that knows it.</h1>
    <p class="mt-3 text-sm text-zinc-500">Organize notes with categories and tags, search by meaning, and ask Brain AI questions answered from your own notes.</p>
    <ul class="mt-6 space-y-2.5 text-sm text-zinc-700">
        @foreach (['Notes, categories, tags, favorites and pins', 'Meaning-based (semantic) search', 'Brain AI chat with cited sources', 'Works with local or cloud AI models'] as $f)
            <li class="flex items-center gap-2"><x-icon name="save" class="w-4 h-4 text-brand-600" /> {{ $f }}</li>
        @endforeach
    </ul>
    <div class="mt-8 flex gap-3">
        <a href="{{ route('register') }}" class="btn-primary flex-1">Get started</a>
        <a href="{{ route('login') }}" class="btn-secondary flex-1">Sign in</a>
    </div>
</x-guest-layout>
