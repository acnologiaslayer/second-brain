<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid md:grid-cols-4 gap-4" style="min-height: calc(100vh - 4rem)">
        <aside class="bg-white rounded-lg shadow-sm p-4 space-y-2 md:col-span-1">
            <form method="POST" action="{{ route('brain.store') }}">@csrf<button class="w-full bg-indigo-600 text-white rounded-md py-2 text-sm">+ New chat</button></form>
            @foreach ($conversations as $c)
                <a href="{{ route('brain.show', $c) }}" class="block text-sm truncate px-2 py-1 rounded {{ $conversation && $conversation->id === $c->id ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50' }}">{{ $c->title }}</a>
            @endforeach
        </aside>
        <section class="md:col-span-3 flex flex-col bg-gray-50 rounded-lg">
            @if (! $conversation)
                <div class="m-auto text-center text-gray-500 p-10">
                    <div class="text-5xl mb-2">🧠</div>
                    <p>Brain AI answers using your saved notes and shows which notes it used.</p>
                    <form method="POST" action="{{ route('brain.store') }}" class="mt-4">@csrf<x-primary-button>Start a conversation</x-primary-button></form>
                </div>
            @else
                <div class="flex justify-between items-center px-4 py-3 border-b bg-white rounded-t-lg">
                    <h2 class="font-semibold truncate">{{ $conversation->title }}</h2>
                    <form method="POST" action="{{ route('brain.destroy', $conversation) }}" onsubmit="return confirm('Delete conversation?')">@csrf @method('DELETE')<button class="text-xs text-red-600">Delete</button></form>
                </div>
                <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-3" style="max-height: 65vh">
                    @forelse ($conversation->messages as $m)
                        @include('brain._message', ['m' => $m])
                    @empty
                        <p id="empty" class="text-center text-gray-400 text-sm mt-10">Ask anything about your notes.</p>
                    @endforelse
                </div>
                <form id="chat-form" method="POST" action="{{ route('brain.messages.store', $conversation) }}" class="p-3 border-t bg-white rounded-b-lg flex gap-2">
                    @csrf
                    <textarea name="content" id="chat-input" rows="2" required maxlength="8000" placeholder="Ask Brain AI... (Enter to send, Shift+Enter for newline)" class="flex-1 rounded-md border-gray-300 text-sm"></textarea>
                    <x-primary-button id="send-btn">Send</x-primary-button>
                </form>
                <script>
                    (() => {
                        const form = document.getElementById('chat-form'), input = document.getElementById('chat-input'),
                              box = document.getElementById('messages'), btn = document.getElementById('send-btn');
                        const scroll = () => box.scrollTop = box.scrollHeight; scroll();
                        const bubble = (html) => { document.getElementById('empty')?.remove(); box.insertAdjacentHTML('beforeend', html); scroll(); };
                        const esc = (s) => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                        input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
                        form.addEventListener('submit', async (e) => {
                            e.preventDefault();
                            const text = input.value.trim(); if (!text) return;
                            bubble(`<div class="flex justify-end"><div class="max-w-[85%] rounded-lg px-4 py-3 bg-indigo-600 text-white"><div class="whitespace-pre-wrap text-sm">${esc(text)}</div></div></div>`);
                            bubble(`<div id="thinking" class="text-sm text-gray-400 animate-pulse">Brain AI is thinking…</div>`);
                            input.value = ''; btn.disabled = true;
                            try {
                                const res = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type': 'application/json' }, body: JSON.stringify({ content: text }) });
                                document.getElementById('thinking')?.remove();
                                if (!res.ok) throw new Error(res.status === 429 ? 'Too many messages. Wait a minute.' : 'Request failed (' + res.status + ')');
                                bubble((await res.json()).message);
                            } catch (err) {
                                document.getElementById('thinking')?.remove();
                                bubble(`<div class="text-sm text-red-600">${esc(err.message)}</div>`);
                            } finally { btn.disabled = false; input.focus(); }
                        });
                    })();
                </script>
            @endif
        </section>
    </div>
</x-app-layout>
