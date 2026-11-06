<x-app-layout>
    <x-slot name="title">Inbox</x-slot>
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <div class="eyebrow">Quick capture</div>
            <h1 class="page-title mt-1">Inbox</h1>
            <p class="text-sm text-zinc-500 mt-1">Dump thoughts fast. Organize them later by converting them into notes.</p>
        </div>

        <form method="POST" action="{{ route('capture') }}" class="card ticks flex items-start focus-within:border-zinc-900 transition-colors" x-data="{ v: '' }">
            @csrf
            <span class="font-mono text-sm text-brand-500 font-bold pl-4 pt-3 select-none">&gt;_</span>
            <textarea name="content" x-model="v" rows="3" required autofocus placeholder="capture a thought, link or idea… (ctrl+enter)"
                      class="flex-1 resize-none border-0 bg-transparent focus:ring-0 font-mono text-sm py-3 px-3 placeholder:text-zinc-400"
                      @keydown.ctrl.enter="v.trim() && $el.form.submit()" @keydown.meta.enter="v.trim() && $el.form.submit()"></textarea>
            <button class="btn-primary m-1.5 shrink-0" :disabled="!v.trim()">Capture</button>
        </form>

        <section>
            <h2 class="eyebrow mb-3">Waiting to be organized · {{ str_pad($inbox->count(), 2, '0', STR_PAD_LEFT) }}</h2>
            <div class="card divide-y divide-zinc-200">
                @forelse ($inbox as $item)
                    <div class="flex items-start gap-3 px-4 py-3">
                        <span class="led bg-warn mt-1.5"></span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-zinc-900 whitespace-pre-wrap break-words">{{ Str::limit($item->content, 300) }}</div>
                            <div class="font-mono text-[10.5px] text-zinc-400 mt-1">#{{ str_pad($item->id, 3, '0', STR_PAD_LEFT) }} · {{ $item->created_at->diffForHumans() }}</div>
                        </div>
                        <form method="POST" action="{{ route('capture.convert', $item) }}">@csrf
                            <button class="btn-secondary btn-sm">Convert to note →</button>
                        </form>
                        <form method="POST" action="{{ route('notes.destroy', $item) }}" onsubmit="return confirm('Move to trash?')">@csrf @method('DELETE')
                            <button class="btn-ghost btn-sm text-zinc-400 hover:text-err" title="Discard"><x-icon name="trash" class="w-4 h-4" /></button>
                        </form>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center font-mono text-xs text-zinc-400">// inbox zero</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
