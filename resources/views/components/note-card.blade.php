@props(['note', 'method' => null, 'trash' => false])
<div class="card group flex flex-col hover:border-zinc-900 hover:shadow-md transition-[border-color,box-shadow] duration-150">
    <div class="flex items-center justify-between gap-2 px-4 py-2 border-b border-dashed border-zinc-200 font-mono text-[10px] uppercase tracking-wider text-zinc-400">
        <span class="flex items-center gap-2">
            <span class="led {{ $note->is_pinned ? 'bg-brand-500' : ($note->note_type === 'inbox' ? 'bg-warn' : 'bg-zinc-300') }}"></span>
            {{ $note->note_type }}<span class="text-zinc-300">·</span>#{{ str_pad($note->id, 3, '0', STR_PAD_LEFT) }}
        </span>
        <span class="flex items-center gap-1.5">
            @if ($method)
                <span class="badge {{ $method === 'keyword' ? 'border-zinc-300 text-zinc-500' : 'border-brand-300 bg-brand-50 text-brand-700' }}">{{ $method === 'semantic' ? '≈ meaning' : ($method === 'hybrid' ? '≈ + kw' : 'kw') }}</span>
            @endif
            @if ($note->is_favorite)<x-icon name="star" class="w-3.5 h-3.5 text-brand-500 fill-brand-500" />@endif
            <span>{{ $note->updated_at->diffForHumans(short: true) }}</span>
        </span>
    </div>
    <div class="px-4 pt-3 pb-4 flex-1 flex flex-col">
        @if ($trash)
            <span class="font-semibold text-zinc-900 leading-snug">{{ $note->title }}</span>
        @else
            <a href="{{ route('notes.show', $note) }}" class="font-semibold text-zinc-900 leading-snug group-hover:text-brand-600 transition-colors">{{ $note->title }}</a>
        @endif
        <p class="mt-1.5 text-sm text-zinc-500 leading-relaxed line-clamp-3 flex-1">{{ Str::limit($note->content, 180) }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-[11px]">
            @if ($note->category)<span class="text-zinc-700">{{ $note->category->name }}</span>@endif
            @foreach ($note->tags->take(3) as $t)<span class="text-zinc-400">#{{ $t->name }}</span>@endforeach
        </div>
    </div>
    @if ($trash)
        <div class="px-4 py-2.5 border-t border-zinc-200 flex gap-2">
            <form method="POST" action="{{ route('notes.restore', $note->id) }}">@csrf<button class="btn-secondary btn-sm"><x-icon name="restore" class="w-3.5 h-3.5" /> Restore</button></form>
            <form method="POST" action="{{ route('notes.force', $note->id) }}" onsubmit="return confirm('Permanently delete this note?')">@csrf @method('DELETE')<button class="btn-danger btn-sm">Purge</button></form>
        </div>
    @endif
</div>
