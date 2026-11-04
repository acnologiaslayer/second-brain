<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800">{{ $note->title }}</h2>
            <div class="flex gap-2 text-sm">
                <form method="POST" action="{{ route('notes.toggle', [$note, 'is_favorite']) }}">@csrf<button class="px-3 py-1 rounded border">{{ $note->is_favorite ? '★ Favorited' : '☆ Favorite' }}</button></form>
                <form method="POST" action="{{ route('notes.toggle', [$note, 'is_pinned']) }}">@csrf<button class="px-3 py-1 rounded border">{{ $note->is_pinned ? '📌 Pinned' : 'Pin' }}</button></form>
                <form method="POST" action="{{ route('notes.summarize', $note) }}">@csrf<button class="px-3 py-1 rounded border">✨ Summarize</button></form>
                <a href="{{ route('notes.edit', $note) }}" class="px-3 py-1 rounded bg-indigo-600 text-white">Edit</a>
                <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Move to trash?')">@csrf @method('DELETE')<button class="px-3 py-1 rounded border text-red-600">Delete</button></form>
            </div>
        </div>
    </x-slot>
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('summary'))
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <div class="font-semibold text-indigo-800 mb-1">AI summary <span class="text-xs font-normal">(suggestion, review before use)</span></div>
                <div class="whitespace-pre-wrap text-sm text-gray-800">{{ session('summary') }}</div>
                <a class="text-sm text-indigo-600 mt-2 inline-block" href="{{ route('notes.create', ['title' => 'Summary: '.$note->title, 'content' => session('summary')]) }}">Save summary as new note →</a>
            </div>
        @endif
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex flex-wrap gap-2 text-xs mb-4">
                <span class="bg-gray-100 px-2 py-0.5 rounded">{{ $note->note_type }}</span>
                @if ($note->category)<span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded">{{ $note->category->name }}</span>@endif
                @foreach ($note->tags as $t)<a href="{{ route('notes.index', ['tag' => $t->id]) }}" class="text-gray-500 hover:text-indigo-600">#{{ $t->name }}</a>@endforeach
                <span class="text-gray-400 ml-auto">Updated {{ $note->updated_at->diffForHumans() }}</span>
            </div>
            <div class="whitespace-pre-wrap text-gray-800 leading-relaxed">{{ $note->content }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-2">🔗 Linked notes</h3>
            <ul class="text-sm space-y-1 mb-3">
                @foreach ($note->outgoingLinks as $l)<li>→ <a class="text-indigo-600" href="{{ route('notes.show', $l->target_note_id) }}">{{ $l->target?->title }}</a> {{ $l->label ? "($l->label)" : '' }}</li>@endforeach
                @foreach ($note->incomingLinks as $l)<li>← <a class="text-indigo-600" href="{{ route('notes.show', $l->source_note_id) }}">{{ $l->source?->title }}</a> {{ $l->label ? "($l->label)" : '' }}</li>@endforeach
                @if ($note->outgoingLinks->isEmpty() && $note->incomingLinks->isEmpty())<li class="text-gray-500">No links yet.</li>@endif
            </ul>
            @if ($linkable->isNotEmpty())
            <form method="POST" action="{{ route('notes.link', $note) }}" class="flex flex-wrap gap-2 text-sm">
                @csrf
                <select name="target_note_id" class="rounded-md border-gray-300 text-sm">@foreach ($linkable as $n)<option value="{{ $n->id }}">{{ $n->title }}</option>@endforeach</select>
                <input name="label" placeholder="label (optional)" class="rounded-md border-gray-300 text-sm">
                <button class="bg-gray-800 text-white px-3 rounded-md">Link</button>
            </form>
            @endif
        </div>
    </div>
</x-app-layout>
