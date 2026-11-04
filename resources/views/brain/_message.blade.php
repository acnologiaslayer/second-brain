<div class="flex {{ $m->role === 'user' ? 'justify-end' : 'justify-start' }}">
    <div class="max-w-[85%] rounded-lg px-4 py-3 {{ $m->role === 'user' ? 'bg-indigo-600 text-white' : ($m->status === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-white shadow-sm text-gray-800') }}">
        <div class="whitespace-pre-wrap text-sm">{{ $m->content }}</div>
        @if ($m->role === 'assistant')
            @php $sources = $m->metadata['sources'] ?? []; @endphp
            @if ($sources)
                <div class="mt-2 pt-2 border-t text-xs text-gray-500">
                    Sources:
                    @foreach ($sources as $s)
                        <a href="{{ route('notes.show', $s['note_id']) }}" class="text-indigo-600 hover:underline">[#{{ $s['note_id'] }}] {{ $s['title'] }}</a>{!! ($s['method'] ?? null) === 'semantic' ? '<span title="matched by meaning" class="text-purple-500">✦</span>' : '' !!}{{ $loop->last ? '' : ', ' }}
                    @endforeach
                </div>
            @endif
            @if ($m->status !== 'error')
                <a href="{{ route('brain.messages.save', $m) }}" class="mt-2 inline-block text-xs text-indigo-600 hover:underline">💾 Save to Brain (review first)</a>
            @endif
        @endif
    </div>
</div>
