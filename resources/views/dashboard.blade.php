<x-app-layout>
    <x-slot name="title">Home</x-slot>
    <div class="max-w-6xl mx-auto space-y-6">
        <div>
            <h1 class="page-title">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ strtok(Auth::user()->name, ' ') }}</h1>
            <p class="text-sm text-zinc-500 mt-1">Here's what's in your second brain.</p>
        </div>

        {{-- Quick capture --}}
        <form method="POST" action="{{ route('capture') }}" class="card p-2 flex items-start gap-2 focus-within:ring-2 focus-within:ring-brand-500/30 focus-within:border-brand-300 transition"
              x-data="{ v: '' }">
            @csrf
            <x-icon name="bolt" class="w-5 h-5 text-amber-500 mt-2.5 ml-2 shrink-0" />
            <textarea name="content" x-model="v" rows="1" required placeholder="Quick capture: jot down a thought, link or idea…"
                      class="flex-1 resize-none border-0 focus:ring-0 text-sm py-2.5 placeholder:text-zinc-400"
                      @input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                      @keydown.ctrl.enter="$el.form.submit()" @keydown.meta.enter="$el.form.submit()"></textarea>
            <button class="btn-primary m-0.5 shrink-0" :disabled="!v.trim()"><span class="hidden sm:inline">Capture</span><x-icon name="plus" class="w-4 h-4 sm:hidden" /></button>
        </form>

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['Notes', $stats['notes'], 'notes', route('notes.index')],
                ['Favorites', $stats['favorites'], 'star', route('notes.index', ['favorites' => 1])],
                ['Categories', $stats['categories'], 'folder', route('categories.index')],
                ['Conversations', $stats['conversations'], 'chat', route('brain.index')],
            ] as [$label, $value, $icon, $href])
                <a href="{{ $href }}" class="card p-4 flex items-center gap-4 hover:border-zinc-300 transition">
                    <span class="grid place-items-center w-10 h-10 rounded-lg bg-zinc-100 text-zinc-500"><x-icon :name="$icon" /></span>
                    <div>
                        <div class="text-2xl font-semibold text-zinc-900 leading-none">{{ $value }}</div>
                        <div class="text-xs text-zinc-500 mt-1">{{ $label }}</div>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($inbox)
            <a href="{{ route('notes.index', ['type' => 'inbox']) }}" class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 hover:bg-amber-100/70 transition">
                <span><strong>{{ $inbox }}</strong> captured {{ Str::plural('item', $inbox) }} in your inbox waiting to be organized</span>
                <span>Review →</span>
            </a>
        @endif

        <div class="grid lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">Recent notes</h2>
                    <a href="{{ route('notes.index') }}" class="text-sm text-brand-600 hover:text-brand-700">View all</a>
                </div>
                @if ($recent->isEmpty())
                    <div class="card p-10 text-center">
                        <x-icon name="notes" class="w-8 h-8 mx-auto text-zinc-300" />
                        <p class="mt-2 text-sm text-zinc-500">No notes yet.</p>
                        <a href="{{ route('notes.create') }}" class="btn-primary mt-4">Create your first note</a>
                    </div>
                @else
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach ($recent as $n)<x-note-card :note="$n" />@endforeach
                    </div>
                @endif
            </section>

            <aside class="space-y-6">
                <a href="{{ route('brain.index') }}" class="block rounded-xl p-5 text-white bg-gradient-to-br from-brand-600 to-violet-600 shadow-sm hover:shadow-md transition">
                    <x-icon name="sparkles" class="w-6 h-6 opacity-90" />
                    <div class="mt-3 font-semibold">Ask Brain AI</div>
                    <p class="text-sm text-white/80 mt-1">Chat with an assistant that answers from your own notes and cites its sources.</p>
                </a>
                <section class="card">
                    <h2 class="px-4 pt-4 pb-2 text-sm font-semibold text-zinc-900 flex items-center gap-2"><x-icon name="pin" class="w-4 h-4 text-zinc-400" /> Pinned</h2>
                    <div class="px-2 pb-2">
                        @forelse ($pinned as $n)
                            <a href="{{ route('notes.show', $n) }}" class="block rounded-lg px-2 py-2 text-sm text-zinc-700 hover:bg-zinc-50 truncate">{{ $n->title }}</a>
                        @empty
                            <p class="px-2 pb-2 text-sm text-zinc-400">Pin important notes to keep them here.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
