<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">{{ $trash ? 'Trash' : 'Notes' }}</h2>
            <a href="{{ route('notes.create') }}" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded-md hover:bg-indigo-700">+ New note</a>
        </div>
    </x-slot>
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        <form method="GET" class="bg-white rounded-lg shadow-sm p-4 grid grid-cols-2 md:grid-cols-6 gap-2 items-center text-sm">
            @if ($trash)<input type="hidden" name="trash" value="1">@endif
            <div class="col-span-2 flex gap-1">
                <input name="q" value="{{ request('q') }}" placeholder="{{ $semantic ? 'Search by meaning or keyword...' : 'Search title or content...' }}" class="flex-1 rounded-md border-gray-300 text-sm">
                <select name="mode" class="rounded-md border-gray-300 text-sm" title="Smart = keyword + meaning, Exact = literal text">
                    <option value="smart" @selected(request('mode', 'smart') === 'smart')>{{ $semantic ? '🧠 Smart' : 'Ranked' }}</option>
                    <option value="exact" @selected(request('mode') === 'exact')>Exact</option>
                </select>
            </div>
            <select name="category" class="rounded-md border-gray-300 text-sm"><option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
            <select name="tag" class="rounded-md border-gray-300 text-sm"><option value="">All tags</option>
                @foreach ($tags as $t)<option value="{{ $t->id }}" @selected(request('tag') == $t->id)>#{{ $t->name }}</option>@endforeach</select>
            <select name="type" class="rounded-md border-gray-300 text-sm"><option value="">All types</option>
                @foreach (\App\Models\Note::TYPES as $t)<option @selected(request('type') == $t)>{{ $t }}</option>@endforeach</select>
            <div class="flex gap-2 items-center">
                <label class="flex items-center gap-1"><input type="checkbox" name="favorites" value="1" @checked(request('favorites')) class="rounded"> ★</label>
                <button class="bg-gray-800 text-white px-3 py-2 rounded-md">Filter</button>
            </div>
        </form>
        @if (request('q') && ! $trash && request('mode', 'smart') === 'smart')
            <p class="text-sm text-gray-500">Ranked by relevance{{ $semantic ? ' using keyword + meaning-based (semantic) search' : ' (keyword only. Set an embedding model in AI Settings to enable meaning-based search)' }}.</p>
        @endif
        <div class="text-sm"><a href="{{ $trash ? route('notes.index') : route('notes.index', ['trash' => 1]) }}" class="text-gray-500 hover:text-indigo-600">{{ $trash ? '← Back to notes' : '🗑 View trash' }}</a></div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($notes as $note)
                <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col">
                    <div class="flex justify-between items-start">
                        @if ($trash)
                            <span class="font-semibold text-gray-800">{{ $note->title }}</span>
                        @else
                            <a href="{{ route('notes.show', $note) }}" class="font-semibold text-gray-800 hover:text-indigo-600">{{ $note->is_pinned ? '📌 ' : '' }}{{ $note->title }}</a>
                        @endif
                        <span class="flex gap-1 items-center">
                            @if ($m = $matchMethods[$note->id] ?? null)<span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded {{ $m === 'semantic' ? 'bg-purple-100 text-purple-700' : ($m === 'hybrid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600') }}">{{ $m === 'semantic' ? 'meaning' : $m }}</span>@endif
                            @if ($note->is_favorite)<span class="text-yellow-500">★</span>@endif
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2 flex-1">{{ Str::limit($note->content, 140) }}</p>
                    <div class="flex flex-wrap gap-1 mt-3 text-xs">
                        <span class="bg-gray-100 px-2 py-0.5 rounded">{{ $note->note_type }}</span>
                        @if ($note->category)<span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">{{ $note->category->name }}</span>@endif
                        @foreach ($note->tags as $t)<span class="text-gray-500">#{{ $t->name }}</span>@endforeach
                    </div>
                    @if ($trash)
                        <div class="flex gap-3 mt-3 text-sm">
                            <form method="POST" action="{{ route('notes.restore', $note->id) }}">@csrf<button class="text-indigo-600">Restore</button></form>
                            <form method="POST" action="{{ route('notes.force', $note->id) }}" onsubmit="return confirm('Permanently delete?')">@csrf @method('DELETE')<button class="text-red-600">Delete forever</button></form>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-gray-500">No notes found.</p>
            @endforelse
        </div>
        {{ $notes->links() }}
    </div>
</x-app-layout>
