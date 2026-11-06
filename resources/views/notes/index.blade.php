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
                <div class="eyebrow">{{ str_pad($notes->total(), 2, '0', STR_PAD_LEFT) }} {{ Str::plural('record', $notes->total()) }}@if (request('q')) · query “{{ request('q') }}”@endif</div>
                <h1 class="page-title mt-1">{{ $heading }}</h1>
            </div>
            @unless ($trash)<a href="{{ route('notes.create') }}" class="btn-primary"><x-icon name="plus" class="w-4 h-4" /> New note</a>@endunless
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2" x-data @change="$el.submit()">
            @if ($trash)<input type="hidden" name="trash" value="1">@endif
            @if (request('favorites'))<input type="hidden" name="favorites" value="1">@endif
            <div class="relative flex-1 min-w-[220px]">
                <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                <input name="q" value="{{ request('q') }}" placeholder="{{ $semantic ? 'Search by meaning or keyword…' : 'Keyword search in title or content…' }}" class="input pl-9" data-search @change.stop>
            </div>
            @unless ($trash)
                <div class="inline-flex border border-zinc-300 bg-paper" title="Smart: keyword + meaning. Exact: literal text.">
                    @foreach (['smart' => $semantic ? 'Smart' : 'Ranked', 'exact' => 'Exact'] as $val => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="{{ $val }}" class="sr-only peer" @checked(request('mode', 'smart') === $val)>
                            <span class="block px-3 py-[9px] font-mono text-[11px] uppercase tracking-wider text-zinc-500 peer-checked:bg-zinc-900 peer-checked:text-zinc-50 transition-colors">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            @endunless
            <select name="category" class="input w-auto"><option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
            <select name="tag" class="input w-auto"><option value="">All tags</option>
                @foreach ($tags as $t)<option value="{{ $t->id }}" @selected(request('tag') == $t->id)>#{{ $t->name }}</option>@endforeach</select>
            @if (! request('q') || request('mode') === 'exact' || $trash)
                <select name="sort" class="input w-auto" title="Sort">
                    @foreach (['updated' => 'Recently updated', 'created' => 'Newest first', 'oldest' => 'Least recently updated', 'title' => 'Title A–Z'] as $v => $l)
                        <option value="{{ $v }}" @selected(request('sort', 'updated') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            @endif
            <select name="type" class="input w-auto"><option value="">All types</option>
                @foreach (\App\Models\Note::TYPES as $t)<option value="{{ $t }}" @selected(request('type') == $t)>{{ ucfirst($t) }}</option>@endforeach</select>
            @if ($filtersOn || request('q'))
                <a href="{{ route('notes.index', $trash ? ['trash' => 1] : []) }}" class="btn-ghost">Clear</a>
            @endif
        </form>

        @if (request('q') && ! $trash && request('mode', 'smart') === 'smart')
            <p class="font-mono text-[11px] text-zinc-500 flex items-center gap-2">
                <span class="led bg-brand-500"></span>
                @if ($semantic) Ranked by relevance using keyword + meaning-based search.
                @else Ranked by keyword. <a href="{{ route('settings.ai') }}" class="text-brand-600 hover:underline">Add an embedding model</a> to search by meaning.
                @endif
            </p>
        @endif

        @if ($notes->isEmpty())
            <div class="card p-12 text-center">
                <p class="font-mono text-xs text-zinc-400">// 0 results</p>
                <p class="mt-2 text-sm font-medium text-zinc-700">{{ $trash ? 'Trash is empty' : (request('q') || $filtersOn ? 'No matching notes' : 'No notes yet') }}</p>
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
