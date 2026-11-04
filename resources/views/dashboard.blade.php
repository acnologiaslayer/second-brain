<x-app-layout>
    <x-slot name="title">Home</x-slot>
    <div class="max-w-6xl mx-auto space-y-7">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="eyebrow">{{ now()->format('l · d M Y') }}</div>
                <h1 class="page-title mt-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ strtok(Auth::user()->name, ' ') }}.</h1>
            </div>
            <a href="{{ route('brain.index') }}" class="btn-secondary"><x-icon name="chat" class="w-4 h-4" /> Ask Brain AI</a>
        </div>

        {{-- Quick capture: terminal prompt --}}
        <form method="POST" action="{{ route('capture') }}" class="card ticks flex items-start focus-within:border-zinc-900 transition-colors" x-data="{ v: '' }">
            @csrf
            <span class="font-mono text-sm text-brand-500 font-bold pl-4 pt-3 select-none">&gt;_</span>
            <textarea name="content" x-model="v" rows="1" required placeholder="capture a thought…"
                      class="flex-1 resize-none border-0 bg-transparent focus:ring-0 font-mono text-sm py-3 px-3 placeholder:text-zinc-400"
                      @input="$el.style.height='auto'; $el.style.height=$el.scrollHeight+'px'"
                      @keydown.ctrl.enter="v.trim() && $el.form.submit()" @keydown.meta.enter="v.trim() && $el.form.submit()"></textarea>
            <button class="btn-primary m-1.5 shrink-0" :disabled="!v.trim()"><span class="hidden sm:inline">Capture</span><x-icon name="plus" class="w-4 h-4 sm:hidden" /></button>
        </form>

        {{-- Readouts --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 border border-zinc-200 bg-paper divide-x divide-zinc-200 [&>*:nth-child(n+3)]:border-t lg:[&>*:nth-child(n+3)]:border-t-0 [&>*:nth-child(3)]:border-l-0 lg:[&>*:nth-child(3)]:border-l">
            @foreach ([
                ['Notes', $stats['notes'], route('notes.index')],
                ['Favorites', $stats['favorites'], route('notes.index', ['favorites' => 1])],
                ['Categories', $stats['categories'], route('categories.index')],
                ['Conversations', $stats['conversations'], route('brain.index')],
            ] as $i => [$label, $value, $href])
                <a href="{{ $href }}" class="group px-5 py-4 hover:bg-zinc-100 transition-colors">
                    <div class="flex items-center justify-between eyebrow text-[9.5px]">
                        <span>{{ $label }}</span><span class="text-zinc-300 group-hover:text-brand-500">↗</span>
                    </div>
                    <div class="mt-2 font-mono text-3xl font-semibold text-zinc-900 tabular-nums">{{ str_pad($value, 2, '0', STR_PAD_LEFT) }}</div>
                </a>
            @endforeach
        </div>

        @if ($inbox)
            <a href="{{ route('notes.index', ['type' => 'inbox']) }}" class="flex items-center gap-3 card px-4 py-3 text-sm hover:border-zinc-900 transition-colors">
                <span class="led bg-warn led-pulse"></span>
                <span class="font-mono text-[10.5px] font-semibold tracking-wider text-zinc-400">INBOX</span>
                <span class="flex-1 text-zinc-700"><strong class="text-zinc-900">{{ $inbox }}</strong> captured {{ Str::plural('item', $inbox) }} waiting to be organized</span>
                <span class="font-mono text-xs text-brand-600">review →</span>
            </a>
        @endif

        <div class="grid lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="eyebrow">Recent notes</h2>
                    <a href="{{ route('notes.index') }}" class="font-mono text-[11px] text-zinc-500 hover:text-brand-600">view all →</a>
                </div>
                @if ($recent->isEmpty())
                    <div class="card p-10 text-center">
                        <p class="font-mono text-sm text-zinc-500">// no notes yet</p>
                        <a href="{{ route('notes.create') }}" class="btn-primary mt-4">Write your first note</a>
                    </div>
                @else
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach ($recent as $n)<x-note-card :note="$n" />@endforeach
                    </div>
                @endif
            </section>

            <aside class="space-y-6">
                <section>
                    <h2 class="eyebrow mb-3">Pinned</h2>
                    <div class="card divide-y divide-zinc-200">
                        @forelse ($pinned as $n)
                            <a href="{{ route('notes.show', $n) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-800 hover:bg-zinc-100 hover:text-brand-600 transition-colors">
                                <span class="led bg-brand-500"></span><span class="truncate">{{ $n->title }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-4 font-mono text-xs text-zinc-400">// pin notes to keep them here</p>
                        @endforelse
                    </div>
                </section>

                <a href="{{ route('brain.index') }}" class="block card ticks p-5 bg-zinc-900 border-zinc-900 text-zinc-50 hover:shadow-md transition-shadow group">
                    <div class="font-mono text-[10px] tracking-label text-brand-400">BRAIN.AI</div>
                    <div class="mt-2 font-semibold text-lg leading-snug">Ask questions.<br>Get answers from your own notes.</div>
                    <div class="mt-4 font-mono text-xs text-zinc-400 group-hover:text-brand-400 transition-colors">open console →</div>
                </a>
            </aside>
        </div>
    </div>
</x-app-layout>
