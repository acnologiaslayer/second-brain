@props(['note', 'method' => null, 'trash' => false])
<div class="card group flex flex-col p-4 hover:border-zinc-300 hover:shadow transition">
    <div class="flex items-start justify-between gap-2">
        @if ($trash)
            <span class="font-medium text-zinc-900 leading-snug">{{ $note->title }}</span>
        @else
            <a href="{{ route('notes.show', $note) }}" class="font-medium text-zinc-900 leading-snug hover:text-brand-700">{{ $note->title }}</a>
        @endif
        <div class="flex items-center gap-1 shrink-0 text-zinc-400">
            @if ($method)
                <span class="badge {{ $method === 'semantic' ? 'bg-violet-50 text-violet-700' : ($method === 'hybrid' ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-600') }}">{{ $method === 'semantic' ? 'meaning' : $method }}</span>
            @endif
            @if ($note->is_pinned)<x-icon name="pin" class="w-4 h-4 text-brand-500" />@endif
            @if ($note->is_favorite)<x-icon name="star" class="w-4 h-4 text-amber-400 fill-amber-400" />@endif
        </div>
    </div>
    <p class="mt-1.5 text-sm text-zinc-500 line-clamp-3 flex-1">{{ Str::limit($note->content, 180) }}</p>
    <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
        @if ($note->category)<span class="badge bg-brand-50 text-brand-700">{{ $note->category->name }}</span>@endif
        <span class="badge bg-zinc-100 text-zinc-600">{{ $note->note_type }}</span>
        @foreach ($note->tags->take(3) as $t)<span class="text-zinc-400">#{{ $t->name }}</span>@endforeach
        <span class="ml-auto text-zinc-400">{{ $note->updated_at->diffForHumans(short: true) }}</span>
    </div>
    @if ($trash)
        <div class="mt-3 pt-3 border-t border-zinc-100 flex gap-2">
            <form method="POST" action="{{ route('notes.restore', $note->id) }}">@csrf<button class="btn-secondary btn-sm"><x-icon name="restore" class="w-3.5 h-3.5" /> Restore</button></form>
            <form method="POST" action="{{ route('notes.force', $note->id) }}" onsubmit="return confirm('Permanently delete this note?')">@csrf @method('DELETE')<button class="btn-danger btn-sm">Delete forever</button></form>
        </div>
    @endif
</div>
