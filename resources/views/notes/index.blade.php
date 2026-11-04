<x-app-layout>
    <x-slot name="title">{{ $trash ? 'Trash' : 'Notes' }}</x-slot>
    @php
        $activeCategory = $categories->firstWhere('id', request('category'));
        $heading = $trash ? 'Trash' : (request('favorites') ? 'Favorites' : ($activeCategory?->name ?? (request('type') === 'inbox' ? 'Inbox' : 'All notes')));
        $filtersOn = request()->hasAny(['category', 'tag', 'type', 'favorites']) && collect(request()->only(['category', 'tag', 'type', 'favorites']))->filter()->isNotEmpty();
    @endphp
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="page-title">{{ $heading }}</h1>
                <p class="text-sm text-zinc-500 mt-1">
                    {{ $notes->total() }} {{ Str::plural('note', $notes->total()) }}
                    @if (request('q')) matching “{{ request('q') }}” @endif
                </p>
            </div>
            @unless ($trash)<a href="{{ route('notes.create') }}" class="btn-primary"><x-icon name="plus" class="w-4 h-4" /> New note</a>@endunless
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2" x-data @change="$el.submit()">
            @if ($trash)<input type="hidden" name="trash" value="1">@endif
            @if (request('favorites'))<input type="hidden" name="favorites" value="1">@endif
            <div class="relative flex-1 min-w-[220px]">
                <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                <input name="q" value="{{ request('q') }}" placeholder="{{ $semantic ? 'Search by meaning or keyword…' : 'Search title or content…' }}" class="input pl-9" data-search @change.stop>
            </div>
            @unless ($trash)
                <div class="inline-flex rounded-lg border border-zinc-200 bg-white p-0.5 text-sm shadow-sm" title="Smart: keyword + meaning. Exact: literal text.">
                    @foreach (['smart' => $semantic ? 'Smart' : 'Ranked', 'exact' => 'Exact'] as $val => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="{{ $val }}" class="sr-only peer" @checked(request('mode', 'smart') === $val)>
                            <span class="block rounded-md px-3 py-1.5 text-zinc-500 peer-checked:bg-zinc-900 peer-checked:text-white transition">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            @endunless
            <select name="category" class="input w-auto"><option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
            <select name="tag" class="input w-auto"><option value="">All tags</option>
                @foreach ($tags as $t)<option value="{{ $t->id }}" @selected(request('tag') == $t->id)>#{{ $t->name }}</option>@endforeach</select>
            <select name="type" class="input w-auto"><option value="">All types</option>
                @foreach (\App\Models\Note::TYPES as $t)<option value="{{ $t }}" @selected(request('type') == $t)>{{ ucfirst($t) }}</option>@endforeach</select>
            @if ($filtersOn || request('q'))
                <a href="{{ route('notes.index', $trash ? ['trash' => 1] : []) }}" class="btn-ghost">Clear</a>
            @endif
        </form>

        @if (request('q') && ! $trash && request('mode', 'smart') === 'smart')
            <p class="text-xs text-zinc-500 flex items-center gap-1.5">
                <x-icon name="sparkles" class="w-3.5 h-3.5 text-violet-500" />
                @if ($semantic) Ranked by relevance using keyword + meaning-based search.
                @else Ranked by keyword. <a href="{{ route('settings.ai') }}" class="text-brand-600 hover:underline">Add an embedding model</a> to search by meaning.
                @endif
            </p>
        @endif

        @if ($notes->isEmpty())
            <div class="card p-12 text-center">
                <x-icon :name="$trash ? 'trash' : 'notes'" class="w-10 h-10 mx-auto text-zinc-300" />
                <p class="mt-3 text-sm font-medium text-zinc-700">{{ $trash ? 'Trash is empty' : (request('q') || $filtersOn ? 'No matching notes' : 'No notes yet') }}</p>
                <p class="text-sm text-zinc-500 mt-1">{{ $trash ? 'Deleted notes appear here and can be restored.' : (request('q') || $filtersOn ? 'Try a different search or clear the filters.' : 'Capture your first idea to get started.') }}</p>
                @if (! $trash && ! request('q') && ! $filtersOn)<a href="{{ route('notes.create') }}" class="btn-primary mt-4">New note</a>@endif
            </div>
        @else
            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($notes as $note)
                    <x-note-card :note="$note" :trash="$trash" :method="$matchMethods[$note->id] ?? null" />
                @endforeach
            </div>
            <div>{{ $notes->links() }}</div>
        @endif
    </div>
</x-app-layout>
