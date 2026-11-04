<x-app-layout>
    <x-slot name="title">Brain AI</x-slot>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -my-6 flex h-[calc(100vh-4rem)]" x-data="{ list: false }">
        {{-- Conversation list --}}
        <aside class="w-64 shrink-0 border-r border-zinc-200 bg-white flex-col hidden md:flex" :class="list && '!flex fixed inset-y-16 left-0 z-20 lg:left-64 shadow-xl'">
            <div class="p-3 border-b border-zinc-100">
                <form method="POST" action="{{ route('brain.store') }}">@csrf<button class="btn-secondary w-full"><x-icon name="plus" class="w-4 h-4" /> New chat</button></form>
            </div>
            <nav class="flex-1 overflow-y-auto p-2 space-y-0.5">
                @forelse ($conversations as $c)
                    <a href="{{ route('brain.show', $c) }}" @class(['block rounded-lg px-3 py-2 text-sm transition', 'bg-zinc-100 text-zinc-900 font-medium' => $conversation?->id === $c->id, 'text-zinc-600 hover:bg-zinc-50' => $conversation?->id !== $c->id])>
                        <div class="truncate" @if($conversation?->id === $c->id) id="conv-side-title" @endif>{{ $c->title }}</div>
                        <div class="text-[11px] text-zinc-400">{{ $c->updated_at->diffForHumans(short: true) }}</div>
                    </a>
                @empty
                    <p class="px-3 py-4 text-xs text-zinc-400">No conversations yet.</p>
                @endforelse
            </nav>
        </aside>

        <section class="flex-1 flex flex-col min-w-0">
            @if (! $conversation)
                <div class="m-auto max-w-md text-center px-6">
                    <span class="mx-auto grid place-items-center w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-500 to-violet-500 text-white shadow"><x-icon name="sparkles" class="w-7 h-7" /></span>
                    <h1 class="mt-5 text-lg font-semibold text-zinc-900">Ask your second brain</h1>
                    <p class="mt-1 text-sm text-zinc-500">Brain AI answers using your saved notes and shows which notes it used.</p>
                    <form method="POST" action="{{ route('brain.store') }}" class="mt-5">@csrf<button class="btn-primary">Start a conversation</button></form>
                </div>
            @else
                <div class="h-12 shrink-0 flex items-center justify-between gap-2 px-4 border-b border-zinc-200 bg-white/70">
                    <div class="flex items-center gap-2 min-w-0">
                        <button class="md:hidden btn-ghost p-1.5" @click="list = !list"><x-icon name="menu" class="w-4 h-4" /></button>
                        <h2 id="conv-title" class="text-sm font-medium text-zinc-900 truncate">{{ $conversation->title }}</h2>
                        @if ($conversation->model)<span class="badge bg-zinc-100 text-zinc-500 hidden sm:inline-flex">{{ $conversation->model }}</span>@endif
                    </div>
                    <form method="POST" action="{{ route('brain.destroy', $conversation) }}" onsubmit="return confirm('Delete this conversation?')">@csrf @method('DELETE')
                        <button class="btn-ghost btn-sm text-zinc-400 hover:text-red-600" title="Delete conversation"><x-icon name="trash" class="w-4 h-4" /></button></form>
                </div>

                <div id="messages" class="flex-1 overflow-y-auto">
                    <div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
                        @forelse ($conversation->messages as $m)
                            @include('brain._message', ['m' => $m])
                        @empty
                            <div id="empty" class="text-center pt-10">
                                <p class="text-sm text-zinc-500">Try asking something about your notes:</p>
                                <div class="mt-4 flex flex-wrap justify-center gap-2">
                                    @foreach (['Summarize what I know about databases', 'What did I note about studying?', 'Which of my notes are related?'] as $s)
                                        <button type="button" data-suggest class="rounded-full border border-zinc-200 bg-white px-3.5 py-1.5 text-sm text-zinc-600 hover:border-brand-300 hover:text-brand-700 transition">{{ $s }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="shrink-0 px-4 pb-4 pt-2">
                    <form id="chat-form" method="POST" action="{{ route('brain.messages.store', $conversation) }}"
                          class="max-w-3xl mx-auto flex items-end gap-2 rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm focus-within:border-brand-300 focus-within:ring-2 focus-within:ring-brand-500/20 transition">
                        @csrf
                        <textarea name="content" id="chat-input" rows="1" required maxlength="8000" placeholder="Ask Brain AI…"
                                  class="flex-1 resize-none border-0 bg-transparent px-2 py-2 text-sm focus:ring-0 max-h-48"></textarea>
                        <button id="send-btn" class="btn-primary rounded-xl p-2.5" title="Send (Enter)"><x-icon name="send" class="w-4 h-4" /></button>
                    </form>
                    <p class="max-w-3xl mx-auto mt-1.5 text-center text-[11px] text-zinc-400">AI can make mistakes. Check the cited notes. Enter to send, Shift+Enter for a new line.</p>
                </div>
            @endif
        </section>
    </div>

    <script>
        // Minimal, safe markdown: escape first, then format bold, italic, code, lists and headings.
        window.renderMd = (el) => {
            const esc = s => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            let h = esc(el.textContent.trim());
            h = h.replace(/```([\s\S]*?)```/g, (_, c) => `<pre class="my-2 overflow-x-auto rounded-lg bg-zinc-900 p-3 text-xs text-zinc-100"><code>${c.trim()}</code></pre>`)
                 .replace(/`([^`\n]+)`/g, '<code class="rounded bg-zinc-100 px-1 py-0.5 text-[13px]">$1</code>')
                 .replace(/\*\*([^*\n]+)\*\*/g, '<strong class="font-semibold text-zinc-900">$1</strong>')
                 .replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>')
                 .replace(/^#{1,4} (.+)$/gm, '<div class="font-semibold text-zinc-900 mt-2">$1</div>')
                 .replace(/^\s*[-*] (.+)$/gm, '<li class="ml-4 list-disc">$1</li>')
                 .replace(/^\s*\d+\. (.+)$/gm, '<li class="ml-4 list-decimal">$1</li>')
                 .replace(/\[Note #(\d+)\]/g, '<span class="text-brand-600 font-medium">[Note #$1]</span>')
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
                add(`<div class="flex justify-end"><div class="max-w-[80%] rounded-2xl rounded-br-md bg-brand-600 px-4 py-2.5 text-sm text-white whitespace-pre-wrap shadow-sm">${esc(text)}</div></div>`);
                add(`<div id="thinking" class="flex gap-3 items-center"><span class="grid place-items-center w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-violet-500"></span><span class="flex gap-1"><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 animate-bounce"></span><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 animate-bounce [animation-delay:150ms]"></span><span class="w-1.5 h-1.5 rounded-full bg-zinc-400 animate-bounce [animation-delay:300ms]"></span></span><span class="text-xs text-zinc-400">Searching your notes and thinking…</span></div>`);
                input.value = ''; grow(); btn.disabled = true;
                try {
                    const res = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ content: text }) });
                    document.getElementById('thinking')?.remove();
                    if (!res.ok) throw new Error(res.status === 429 ? 'Too many messages. Please wait a minute.' : 'Request failed (' + res.status + ').');
                    const data = await res.json(); add(data.message);
                    ['conv-title', 'conv-side-title'].forEach(id => { const el = document.getElementById(id); if (el && data.title) el.textContent = data.title; });
                } catch (err) {
                    document.getElementById('thinking')?.remove();
                    add(`<p class="text-center text-sm text-red-600">${esc(err.message)}</p>`);
                } finally { btn.disabled = false; input.focus(); }
            });
        })();
    </script>
</x-app-layout>
