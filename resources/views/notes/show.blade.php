<x-app-layout>
    <x-slot name="title">{{ $note->title }}</x-slot>
    <div class="max-w-5xl mx-auto grid lg:grid-cols-[1fr_280px] gap-6">
        <article class="space-y-4 min-w-0">
            <div class="flex items-center justify-between gap-2">
                <a href="{{ route('notes.index') }}" class="btn-ghost -ml-3"><x-icon name="arrow-left" class="w-4 h-4" /> Notes</a>
                <div class="flex items-center gap-1">
                    <form method="POST" action="{{ route('notes.toggle', [$note, 'is_favorite']) }}">@csrf
                        <button class="btn-ghost p-2" title="{{ $note->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}"><x-icon name="star" @class(['w-5 h-5', 'text-brand-500 fill-brand-500' => $note->is_favorite]) /></button></form>
                    <form method="POST" action="{{ route('notes.toggle', [$note, 'is_pinned']) }}">@csrf
                        <button class="btn-ghost p-2" title="{{ $note->is_pinned ? 'Unpin' : 'Pin to home' }}"><x-icon name="pin" @class(['w-5 h-5', 'text-brand-500 fill-brand-100' => $note->is_pinned]) /></button></form>
                    <a href="{{ route('notes.edit', $note) }}" class="btn-secondary"><x-icon name="pencil" class="w-4 h-4" /> Edit</a>
                </div>
            </div>

            @if (session('summary'))
                <div class="card ticks p-5 border-brand-300 bg-brand-50/50">
                    <div class="flex items-center gap-2 font-mono text-[10.5px] font-semibold tracking-label text-brand-700"><span class="led bg-brand-500"></span> AI SUMMARY</div>
                    <div class="mt-2 whitespace-pre-wrap text-sm text-zinc-700 leading-relaxed">{{ session('summary') }}</div>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="font-mono text-[11px] text-zinc-500">// suggestion. verify before relying on it</span>
                        <a class="btn-secondary btn-sm" href="{{ route('notes.create', ['title' => 'Summary: '.$note->title, 'content' => session('summary')]) }}">Save as note</a>
                    </div>
                </div>
            @endif

            <div class="card ticks">
                <div class="px-6 sm:px-8 py-2 border-b border-dashed border-zinc-200 font-mono text-[10px] tracking-wider text-zinc-400 flex justify-between">
                    <span>RECORD #{{ str_pad($note->id, 3, '0', STR_PAD_LEFT) }} · {{ strtoupper($note->note_type) }}</span>
                    <span>{{ str_word_count($note->content) }} WORDS</span>
                </div>
                <div class="px-6 sm:px-8 py-6">
                <h1 class="text-2xl font-semibold text-zinc-900 tracking-tight">{{ $note->title }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    @if ($note->category)<a href="{{ route('notes.index', ['category' => $note->category_id]) }}" class="badge border-zinc-900 text-zinc-900 hover:bg-zinc-900 hover:text-zinc-50">{{ $note->category->name }}</a>@endif
                    
                    @foreach ($note->tags as $t)<a href="{{ route('notes.index', ['tag' => $t->id]) }}" class="font-mono text-zinc-500 hover:text-brand-600">#{{ $t->name }}</a>@endforeach
                </div>
                <div class="mt-6 whitespace-pre-wrap text-[15.5px] leading-7 text-zinc-800">{{ $note->content }}</div>
                </div>
            </div>
        </article>

        <aside class="space-y-4 lg:pt-12">
            <div class="card card-pad space-y-3">
                <form method="POST" action="{{ route('notes.summarize', $note) }}" x-data="{ busy: false }" @submit="busy = true">@csrf
                    <button class="btn-secondary w-full" :disabled="busy">
                        <span x-text="busy ? 'Summarizing…' : 'Summarize with AI'">Summarize with AI</span>
                    </button>
                </form>
                <a href="{{ route('brain.index') }}" class="btn-ghost w-full"><x-icon name="chat" class="w-4 h-4" /> Ask Brain AI</a>
                <dl class="pt-3 border-t border-dashed border-zinc-200 font-mono text-[11px] space-y-1.5">
                    <div class="flex justify-between"><dt class="text-zinc-400">CREATED</dt><dd class="text-zinc-600">{{ $note->created_at->format('M j, Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-400">UPDATED</dt><dd class="text-zinc-600">{{ $note->updated_at->diffForHumans() }}</dd></div>
                    
                </dl>
            </div>

            <div class="card card-pad">
                <h3 class="eyebrow">Linked notes</h3>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($note->outgoingLinks as $l)
                        <li><a class="flex items-center gap-2 rounded-md px-2 py-1 -mx-2 text-zinc-700 hover:bg-zinc-50" href="{{ route('notes.show', $l->target_note_id) }}"><span class="text-zinc-300">→</span><span class="truncate">{{ $l->target?->title }}</span>@if($l->label)<span class="ml-auto text-xs text-zinc-400">{{ $l->label }}</span>@endif</a></li>
                    @endforeach
                    @foreach ($note->incomingLinks as $l)
                        <li><a class="flex items-center gap-2 rounded-md px-2 py-1 -mx-2 text-zinc-700 hover:bg-zinc-50" href="{{ route('notes.show', $l->source_note_id) }}"><span class="text-zinc-300">←</span><span class="truncate">{{ $l->source?->title }}</span>@if($l->label)<span class="ml-auto text-xs text-zinc-400">{{ $l->label }}</span>@endif</a></li>
                    @endforeach
                    @if ($note->outgoingLinks->isEmpty() && $note->incomingLinks->isEmpty())<li class="font-mono text-zinc-400 text-[11px]">// no links yet</li>@endif
                </ul>
                @if ($linkable->isNotEmpty())
                    <form method="POST" action="{{ route('notes.link', $note) }}" class="mt-3 pt-3 border-t border-zinc-200 space-y-2" x-data="{ open: false }">
                        @csrf
                        <button type="button" x-show="!open" @click="open = true" class="font-mono text-[11px] text-brand-600 hover:text-brand-700">+ link a note</button>
                        <div x-show="open" x-cloak class="space-y-2">
                            <select name="target_note_id" class="input">@foreach ($linkable as $n)<option value="{{ $n->id }}">{{ $n->title }}</option>@endforeach</select>
                            <input name="label" placeholder="Label (optional)" class="input">
                            <div class="flex gap-2"><button class="btn-primary btn-sm">Add link</button><button type="button" @click="open = false" class="btn-ghost btn-sm">Cancel</button></div>
                        </div>
                    </form>
                @endif
            </div>

            <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Move this note to trash?')">@csrf @method('DELETE')
                <button class="btn-danger w-full"><x-icon name="trash" class="w-4 h-4" /> Move to trash</button>
            </form>
        </aside>
    </div>
</x-app-layout>
