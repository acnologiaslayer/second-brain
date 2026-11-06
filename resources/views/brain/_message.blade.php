@if ($m->role === 'user')
    <div class="rise">
        <div class="font-mono text-[10px] tracking-label text-zinc-400 mb-1.5">YOU <span class="text-zinc-300">· {{ $m->created_at?->format('H:i') }}</span></div>
        <div class="border-l-2 border-zinc-900 pl-4 text-[15px] text-zinc-900 whitespace-pre-wrap">{{ $m->content }}</div>
    </div>
@else
    @php $sources = $m->metadata['sources'] ?? []; @endphp
    <div class="rise">
        <div class="font-mono text-[10px] tracking-label mb-1.5 flex items-center gap-2 {{ $m->status === 'error' ? 'text-err' : 'text-brand-600' }}">
            <span class="led {{ $m->status === 'error' ? 'bg-err' : 'bg-brand-500' }}"></span>
            BRAIN.AI <span class="text-zinc-300">· {{ $m->created_at?->format('H:i') }}@if (! empty($m->metadata['model'])) · {{ $m->metadata['model'] }}@endif</span>
        </div>
        @if ($m->status === 'error')
            <div class="card border-err/40 bg-err/5 px-4 py-3 font-mono text-[13px] text-err">
                ERR: {{ $m->content }}
                <a href="{{ route('settings.ai') }}" class="block mt-1 text-xs underline">→ check AI settings</a>
            </div>
        @else
            <div class="card px-5 py-4 text-[14.5px] text-zinc-800 leading-relaxed" data-md>{{ $m->content }}</div>
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                @if ($sources)<span class="font-mono text-[10px] tracking-wider text-zinc-400 mr-1">SOURCES</span>@endif
                @foreach ($sources as $s)
                    <a href="{{ route('notes.show', $s['note_id']) }}" class="badge border-zinc-300 bg-paper text-zinc-700 normal-case tracking-normal hover:border-zinc-900 hover:text-brand-600 transition-colors" title="{{ ($s['method'] ?? '') === 'semantic' ? 'Matched by meaning' : 'Matched by keyword' }}">
                        <span class="text-zinc-400">#{{ str_pad($s['note_id'], 3, '0', STR_PAD_LEFT) }}</span>&nbsp;{{ Str::limit($s['title'], 30) }}@if (($s['method'] ?? null) === 'semantic')<span class="text-brand-500 ml-1">≈</span>@endif
                    </a>
                @endforeach
                <span class="flex-1"></span>
                <form method="POST" action="{{ route('brain.messages.save', $m) }}" x-data="{ busy: false }" @submit="busy = true">@csrf
                    <button class="btn-ghost btn-sm" :disabled="busy"><x-icon name="save" class="w-3.5 h-3.5" /> <span x-text="busy ? 'Preparing…' : 'Save to notes'">Save to notes</span></button>
                </form>
                <button type="button" onclick="navigator.clipboard.writeText(this.closest('.rise').querySelector('[data-md]').innerText); this.textContent='Copied'" class="btn-ghost btn-sm">Copy</button>
            </div>
        @endif
    </div>
@endif
