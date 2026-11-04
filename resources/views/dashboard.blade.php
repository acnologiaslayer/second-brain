<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Dashboard</h2></x-slot>
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach (['notes' => 'Notes', 'categories' => 'Categories', 'favorites' => 'Favorites', 'conversations' => 'Conversations'] as $k => $label)
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $stats[$k] }}</div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-2">⚡ Quick Capture</h3>
            <form method="POST" action="{{ route('capture') }}" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <textarea name="content" rows="2" required placeholder="Dump a thought, link or idea. Organize it later." class="flex-1 rounded-md border-gray-300"></textarea>
                <x-primary-button>Capture</x-primary-button>
            </form>
            @if ($inbox)<p class="text-sm text-gray-500 mt-2"><a class="text-indigo-600" href="{{ route('notes.index', ['type' => 'inbox']) }}">{{ $inbox }} item(s) in inbox</a> waiting to be organized.</p>@endif
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-3">📌 Pinned</h3>
                @forelse ($pinned as $n)
                    <a href="{{ route('notes.show', $n) }}" class="block py-2 border-b last:border-0 hover:text-indigo-600">{{ $n->title }}</a>
                @empty <p class="text-sm text-gray-500">No pinned notes yet.</p> @endforelse
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex justify-between mb-3"><h3 class="font-semibold text-gray-800">🕑 Recent</h3><a href="{{ route('notes.create') }}" class="text-sm text-indigo-600">+ New note</a></div>
                @forelse ($recent as $n)
                    <a href="{{ route('notes.show', $n) }}" class="flex justify-between py-2 border-b last:border-0 hover:text-indigo-600">
                        <span>{{ $n->title }}</span><span class="text-xs text-gray-400">{{ $n->updated_at->diffForHumans() }}</span>
                    </a>
                @empty <p class="text-sm text-gray-500">Start by <a class="text-indigo-600" href="{{ route('notes.create') }}">creating a note</a>.</p> @endforelse
            </div>
        </div>

        <a href="{{ route('brain.index') }}" class="block bg-indigo-600 text-white rounded-lg p-5 hover:bg-indigo-700">
            <div class="font-semibold">🧠 Ask Brain AI</div>
            <div class="text-sm text-indigo-100">Chat with an assistant grounded in your own notes.</div>
        </a>
    </div>
</x-app-layout>
