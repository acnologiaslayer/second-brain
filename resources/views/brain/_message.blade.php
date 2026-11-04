@if ($m->role === 'user')
    <div class="flex justify-end">
        <div class="max-w-[80%] rounded-2xl rounded-br-md bg-brand-600 px-4 py-2.5 text-sm text-white whitespace-pre-wrap shadow-sm">{{ $m->content }}</div>
    </div>
@else
    @php $sources = $m->metadata['sources'] ?? []; @endphp
    <div class="flex gap-3">
        <span class="shrink-0 grid place-items-center w-8 h-8 rounded-full {{ $m->status === 'error' ? 'bg-red-100 text-red-600' : 'bg-gradient-to-br from-brand-500 to-violet-500 text-white' }}"><x-icon name="sparkles" class="w-4 h-4" /></span>
        <div class="min-w-0 max-w-[85%] space-y-2">
            @if ($m->status === 'error')
                <div class="rounded-2xl rounded-tl-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{{ $m->content }}
                    <a href="{{ route('settings.ai') }}" class="block mt-1 text-xs font-medium underline">Check AI settings</a></div>
            @else
                <div class="md rounded-2xl rounded-tl-md border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 leading-relaxed shadow-sm" data-md>{{ $m->content }}</div>
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    @foreach ($sources as $s)
                        <a href="{{ route('notes.show', $s['note_id']) }}" class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white px-2 py-1 text-zinc-600 hover:border-brand-300 hover:text-brand-700 transition" title="{{ ($s['method'] ?? '') === 'semantic' ? 'Matched by meaning' : 'Matched by keyword' }}">
                            <x-icon name="notes" class="w-3 h-3" /> {{ Str::limit($s['title'], 32) }}
                            @if (($s['method'] ?? null) === 'semantic')<span class="text-violet-500">✦</span>@endif
                        </a>
                    @endforeach
                    <a href="{{ route('brain.messages.save', $m) }}" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 transition">
                        <x-icon name="save" class="w-3.5 h-3.5" /> Save to notes</a>
                    <button type="button" onclick="navigator.clipboard.writeText(this.closest('.space-y-2').querySelector('[data-md]').innerText); this.textContent='Copied'" class="rounded-md px-2 py-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800">Copy</button>
                </div>
            @endif
        </div>
    </div>
@endif
