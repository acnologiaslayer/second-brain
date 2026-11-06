<x-app-layout>
    <x-slot name="title">Brain AI</x-slot>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -my-7 flex h-[calc(100vh-4rem)]" x-data="{ list: false }">
        {{-- Session list --}}
        <aside class="w-64 shrink-0 border-r border-zinc-200 bg-zinc-50 flex-col hidden md:flex" :class="list && '!flex fixed inset-y-16 left-0 z-20 lg:left-60 shadow-xl'">
            <div class="p-3 border-b border-zinc-200">
                <form method="POST" action="{{ route('brain.store') }}">@csrf<button class="btn-secondary w-full"><x-icon name="plus" class="w-4 h-4" /> New session</button></form>
            </div>
            <div class="px-4 pt-3 pb-1 eyebrow text-[9.5px] text-zinc-400">Sessions</div>
            <nav class="flex-1 overflow-y-auto pb-2">
                @forelse ($conversations as $c)
                    <a href="{{ route('brain.show', $c) }}" @class(['block border-l-2 px-4 py-2 text-sm transition-colors', 'border-brand-500 bg-brand-50 text-zinc-900' => $conversation?->id === $c->id, 'border-transparent text-zinc-600 hover:bg-zinc-100' => $conversation?->id !== $c->id])>
                        <div class="truncate" @if($conversation?->id === $c->id) id="conv-side-title" @endif>{{ $c->title }}</div>
                        <div class="font-mono text-[10px] text-zinc-400">{{ $c->updated_at->diffForHumans(short: true) }}</div>
                    </a>
                @empty
                    <p class="px-4 py-3 font-mono text-[11px] text-zinc-400">// none yet</p>
                @endforelse
            </nav>
        </aside>

        <section class="flex-1 flex flex-col min-w-0">
            @if (! $conversation)
                <div class="m-auto max-w-md text-center px-6">
                    <div class="font-mono text-[10px] tracking-label text-brand-600">BRAIN.AI · READY</div>
                    <h1 class="mt-3 text-2xl font-semibold text-zinc-900 tracking-tight caret">Ask your second brain</h1>
                    <p class="mt-2 text-sm text-zinc-500">Answers come from your saved notes, with the notes it used listed as sources.</p>
                    <form method="POST" action="{{ route('brain.store') }}" class="mt-6">@csrf<button class="btn-primary">Start a session</button></form>
                </div>
            @else
                <div class="h-12 shrink-0 flex items-center justify-between gap-2 px-4 border-b border-zinc-200 bg-zinc-50">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <button class="md:hidden btn-ghost p-1.5" @click="list = !list"><x-icon name="menu" class="w-4 h-4" /></button>
                        <span class="led bg-ok"></span>
                        <h2 id="conv-title" class="text-sm font-medium text-zinc-900 truncate">{{ $conversation->title }}</h2>
                    </div>
                    @php $cfg = app(\App\Services\AI\AIProviderManager::class)->settingsFor(auth()->user()); @endphp
                    <div class="flex items-center gap-2">
                        <a href="{{ route('settings.ai') }}" class="hidden sm:inline-flex badge border-zinc-300 text-zinc-600 gap-1.5 hover:border-zinc-900" title="Active provider and model">
                            <span class="led bg-ok"></span>{{ \App\Models\AISetting::PROVIDERS[$cfg->provider]['label'] ?? $cfg->provider }} · {{ $cfg->model ?: 'no model' }}
                        </a>
                        <span class="hidden md:inline-flex badge {{ $cfg->embedding_model ? 'border-brand-300 text-brand-700' : 'border-zinc-300 text-zinc-500' }}" title="Retrieval method used for answers">
                            {{ $cfg->embedding_model ? 'retrieval: keyword + semantic' : 'retrieval: keyword' }}
                        </span>
                        <form method="POST" action="{{ route('brain.destroy', $conversation) }}" onsubmit="return confirm('Delete this session?')">@csrf @method('DELETE')
                            <button class="btn-ghost btn-sm text-zinc-400 hover:text-err" title="Delete session"><x-icon name="trash" class="w-4 h-4" /></button></form>
                    </div>
                </div>

                <div id="messages" class="flex-1 overflow-y-auto">
                    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-7">
                        @forelse ($conversation->messages as $m)
                            @include('brain._message', ['m' => $m])
                        @empty
                            <div id="empty" class="pt-10 text-center">
                                <p class="font-mono text-[11px] tracking-wider text-zinc-400">// TRY ASKING</p>
                                <div class="mt-4 flex flex-wrap justify-center gap-2">
                                    @foreach (['Summarize what I know about databases', 'What did I note about studying?', 'Which of my notes are related?'] as $s)
                                        <button type="button" data-suggest class="border border-zinc-300 bg-paper px-3.5 py-1.5 text-sm text-zinc-700 hover:border-zinc-900 hover:text-brand-600 transition-colors">{{ $s }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="shrink-0 px-4 sm:px-6 pb-4 pt-2">
                    <form id="chat-form" method="POST" action="{{ route('brain.messages.store', $conversation) }}"
                          class="card ticks max-w-3xl mx-auto flex items-end gap-2 p-1.5 focus-within:border-zinc-900 transition-colors">
                        @csrf
                        <span class="font-mono text-sm font-bold text-brand-500 pl-2.5 pb-2.5 select-none">&gt;</span>
                        <textarea name="content" id="chat-input" rows="1" required maxlength="8000" placeholder="Ask about your notes…"
                                  class="flex-1 resize-none border-0 bg-transparent px-1 py-2 text-[15px] focus:ring-0 max-h-48 placeholder:text-zinc-400"></textarea>
                        <button id="send-btn" class="btn-primary" title="Send (Enter)">Send <span class="opacity-70">↵</span></button>
                    </form>
                    <p class="max-w-3xl mx-auto mt-2 font-mono text-[10.5px] text-zinc-400 flex justify-between"><span>AI can be wrong. Check the cited notes.</span><span class="hidden sm:inline">enter send · shift+enter newline</span></p>
                </div>
            @endif
        </section>
    </div>

    <script>
        // Minimal, safe markdown: escape first, then format bold, italic, code, lists and headings.
        window.renderMd = (el) => {
            const esc = s => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            let h = esc(el.textContent.trim());
            h = h.replace(/```([\s\S]*?)```/g, (_, c) => `<pre class="my-2 overflow-x-auto bg-zinc-900 p-3 font-mono text-xs text-zinc-100"><code>${c.trim()}</code></pre>`)
                 .replace(/`([^`\n]+)`/g, '<code class="bg-zinc-100 border border-zinc-200 px-1 font-mono text-[12.5px]">$1</code>')
                 .replace(/\*\*([^*\n]+)\*\*/g, '<strong class="font-semibold text-zinc-900">$1</strong>')
                 .replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>')
                 .replace(/^#{1,4} (.+)$/gm, '<div class="font-semibold text-zinc-900 mt-2">$1</div>')
                 .replace(/^\s*[-*] (.+)$/gm, '<li class="ml-5 list-[square] marker:text-brand-500">$1</li>')
                 .replace(/^\s*\d+\. (.+)$/gm, '<li class="ml-5 list-decimal marker:font-mono marker:text-brand-600">$1</li>')
                 .replace(/\[Note #(\d+)\]/g, '<span class="font-mono text-[12px] text-brand-600">[#$1]</span>')
                 .replace(/\n{2,}/g, '<div class="h-2"></div>').replace(/\n/g, '<br>')
                 .replace(/<br>(<li|<\/li>)/g, '$1').replace(/(<\/li>)<br>/g, '$1');
            el.innerHTML = h; el.classList.remove('whitespace-pre-wrap');
        };
        document.querySelectorAll('[data-md]').forEach(renderMd);

        (() => {
            const form = document.getElementById('chat-form'); if (!form) return;
            const input = document.getElementById('chat-input'), scroller = document.getElementById('messages'),
                  box = scroller.firstElementChild, btn = document.getElementById('send-btn');
            const scroll = () => scroller.scrollTop = scroller.scrollHeight; scroll();
            const grow = () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 192) + 'px'; };
            const add = (html) => { document.getElementById('empty')?.remove(); box.insertAdjacentHTML('beforeend', html); box.querySelectorAll('[data-md]:not([data-done])').forEach(el => { renderMd(el); el.dataset.done = 1; }); scroll(); };
            const esc = s => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            input.addEventListener('input', grow);
            input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
            document.querySelectorAll('[data-suggest]').forEach(b => b.onclick = () => { input.value = b.textContent.trim(); form.requestSubmit(); });
            input.focus();
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const text = input.value.trim(); if (!text || btn.disabled) return;
                add(`<div class="rise"><div class="font-mono text-[10px] tracking-label text-zinc-400 mb-1.5">YOU</div><div class="border-l-2 border-zinc-900 pl-4 text-[15px] text-zinc-900 whitespace-pre-wrap">${esc(text)}</div></div>`);
                add(`<div id="thinking" class="rise font-mono text-[11px] text-zinc-500 space-y-1"><div class="flex items-center gap-2 text-brand-600"><span class="led bg-brand-500 led-pulse"></span>BRAIN.AI</div><div>› searching notes…</div><div class="caret">› composing answer</div></div>`);
                input.value = ''; grow(); btn.disabled = true;
                try {
                    const res = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ content: text }) });
                    document.getElementById('thinking')?.remove();
                    if (!res.ok) throw new Error(res.status === 429 ? 'Too many messages. Please wait a minute.' : 'Request failed (' + res.status + ').');
                    const data = await res.json(); add(data.message);
                    ['conv-title', 'conv-side-title'].forEach(id => { const el = document.getElementById(id); if (el && data.title) el.textContent = data.title; });
                } catch (err) {
                    document.getElementById('thinking')?.remove();
                    add(`<p class="font-mono text-xs text-err">ERR: ${esc(err.message)}</p>`);
                } finally { btn.disabled = false; input.focus(); }
            });
        })();
    </script>
</x-app-layout>
