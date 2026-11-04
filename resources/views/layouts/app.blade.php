<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Second Brain</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans" x-data="{ sidebar: false }" @keydown.window.escape="sidebar = false">
    @php
        $user = Auth::user();
        $ai = app(\App\Services\AI\AIProviderManager::class)->settingsFor($user);
        $sections = [
            'Workspace' => [
                ['dashboard', 'Home', 'home', request()->routeIs('dashboard'), '01'],
                ['notes.index', 'Notes', 'notes', request()->routeIs('notes.*') && ! request('trash') && ! request('favorites'), '02'],
                ['brain.index', 'Brain AI', 'chat', request()->routeIs('brain.*'), '03'],
            ],
            'Library' => [
                ['categories.index', 'Organize', 'folder', request()->routeIs('categories.*'), '04'],
                [['notes.index', ['favorites' => 1]], 'Favorites', 'star', (bool) request('favorites'), '05'],
                [['notes.index', ['trash' => 1]], 'Trash', 'trash', (bool) request('trash'), '06'],
            ],
        ];
        $categories = $user->categories()->withCount('notes')->orderBy('name')->limit(8)->get();
    @endphp

    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-zinc-900/30 lg:hidden" @click="sidebar = false"></div>

    {{-- ── Sidebar ───���─────────────────────────────────────── --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-60 flex flex-col bg-zinc-50 border-r border-zinc-200 transition-transform duration-200 lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="h-16 flex items-center justify-between px-4 border-b border-zinc-200">
            <a href="{{ route('dashboard') }}" class="group block leading-none">
                <div class="font-mono text-[14px] font-bold tracking-[0.16em] text-zinc-900"><span class="text-brand-500">◆</span> SECOND BRAIN</div>
                <div class="mt-1 font-mono text-[9px] tracking-[0.24em] text-zinc-400">KNOWLEDGE CONSOLE · v1</div>
            </a>
            <button class="lg:hidden btn-ghost p-1.5" @click="sidebar = false" aria-label="Close menu"><x-icon name="x" /></button>
        </div>

        <div class="p-4">
            <a href="{{ route('notes.create') }}" class="btn-primary w-full"><x-icon name="plus" class="w-4 h-4" /> New note</a>
        </div>

        <nav class="flex-1 overflow-y-auto pb-4 space-y-5">
            @foreach ($sections as $section => $items)
                <div>
                    <div class="px-4 mb-1.5 eyebrow text-[9.5px] text-zinc-400">{{ $section }}</div>
                    @foreach ($items as [$route, $label, $icon, $active, $idx])
                        <a href="{{ is_array($route) ? route($route[0], $route[1]) : route($route) }}" @class(['nav-item', 'nav-item-active' => $active])>
                            <x-icon :name="$icon" class="w-[18px] h-[18px] {{ $active ? 'text-brand-500' : 'text-zinc-400' }}" />
                            <span class="flex-1">{{ $label }}</span>
                            <span class="font-mono text-[9.5px] text-zinc-300">{{ $idx }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach

            @if ($categories->isNotEmpty())
                <div>
                    <div class="px-4 mb-1.5 eyebrow text-[9.5px] text-zinc-400">Categories</div>
                    @foreach ($categories as $c)
                        <a href="{{ route('notes.index', ['category' => $c->id]) }}" @class(['nav-item py-1.5', 'nav-item-active' => request('category') == $c->id])>
                            <span class="led {{ request('category') == $c->id ? 'bg-brand-500' : 'bg-zinc-300' }} mx-[5px]"></span>
                            <span class="flex-1 truncate">{{ $c->name }}</span>
                            <span class="font-mono text-[10px] text-zinc-400">{{ str_pad($c->notes_count, 2, '0', STR_PAD_LEFT) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </nav>

        <div class="border-t border-zinc-200">
            <a href="{{ route('settings.ai') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('settings.*')])>
                <x-icon name="cog" class="w-[18px] h-[18px] text-zinc-400" /> <span class="flex-1">AI Settings</span>
            </a>
            <div class="px-4 py-2 flex items-center gap-2 font-mono text-[10px] uppercase tracking-wider text-zinc-500 border-t border-dashed border-zinc-200">
                <span class="led bg-ok led-pulse"></span>
                <span class="truncate">{{ $ai->model ?: 'no model' }}</span>
                @if ($ai->embedding_model)<span class="ml-auto text-brand-600" title="Meaning-based search on">SEM</span>@endif
            </div>
            <div x-data="{ open: false }" class="relative border-t border-zinc-200">
                <button @click="open = !open" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-100 transition-colors text-left">
                    <span class="grid place-items-center w-7 h-7 bg-zinc-900 text-zinc-50 font-mono text-[11px] font-bold">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm text-zinc-900 truncate">{{ $user->name }}</span>
                        <span class="block font-mono text-[10px] text-zinc-400 truncate">{{ $user->email }}</span>
                    </span>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute bottom-full left-2 right-2 mb-1 card shadow-md py-1">
                    <a href="{{ route('profile.edit') }}" class="nav-item"><x-icon name="user" class="w-4 h-4" /> Profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="nav-item w-full"><x-icon name="logout" class="w-4 h-4" /> Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── Main ────────────────────────────────────────────── --}}
    <div class="lg:pl-60 min-h-full flex flex-col">
        <header class="sticky top-0 z-20 h-16 flex items-center gap-3 px-4 sm:px-6 lg:px-8 bg-zinc-50/90 backdrop-blur border-b border-zinc-200">
            <button class="lg:hidden btn-ghost p-1.5 -ml-1.5" @click="sidebar = true" aria-label="Open menu"><x-icon name="menu" /></button>
            <div class="hidden md:block font-mono text-[11px] uppercase tracking-wider text-zinc-400 shrink-0">
                ~/<span class="text-zinc-700">{{ strtolower($title ?? 'home') }}</span>
            </div>
            @if (request()->routeIs('notes.index'))
                <div class="flex-1"></div>
            @else
                <form action="{{ route('notes.index') }}" method="GET" class="flex-1 max-w-lg md:ml-6 relative">
                    <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                    <input name="q" placeholder="Search notes by meaning or keyword" class="input pl-9 pr-10" data-search>
                    <span class="kbd hidden sm:block absolute right-2.5 top-1/2 -translate-y-1/2">/</span>
                </form>
            @endif
            <div class="hidden sm:block ml-auto font-mono text-[11px] text-zinc-400 tabular-nums" x-data="{ t: '' }" x-init="const f = () => t = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); f(); setInterval(f, 15000)" x-text="t"></div>
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-7">
            @foreach (['status' => ['bg-ok', 'OK'], 'error' => ['bg-err', 'ERR']] as $key => [$led, $code])
                @if (is_string(session($key)) && ! in_array(session($key), ['profile-updated', 'password-updated', 'verification-link-sent']))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
                         class="rise mb-5 max-w-6xl mx-auto flex items-center gap-3 card px-4 py-2.5 text-sm">
                        <span class="led {{ $led }}"></span>
                        <span class="font-mono text-[10.5px] font-semibold tracking-wider text-zinc-400">{{ $code }}</span>
                        <span class="flex-1 text-zinc-800">{{ session($key) }}</span>
                        <button @click="show = false" class="text-zinc-400 hover:text-zinc-900"><x-icon name="x" class="w-4 h-4" /></button>
                    </div>
                @endif
            @endforeach

            {{ $slot }}
        </main>
    </div>
</body>
</html>
