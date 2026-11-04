<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Second Brain</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans" x-data="{ sidebar: false }" @keydown.window.escape="sidebar = false">
    @php
        $user = Auth::user();
        $nav = [
            ['dashboard', 'Home', 'home', 'dashboard'],
            ['notes.index', 'Notes', 'notes', 'notes.*'],
            ['brain.index', 'Brain AI', 'sparkles', 'brain.*'],
            ['categories.index', 'Organize', 'folder', 'categories.*'],
        ];
        $categories = $user->categories()->withCount('notes')->orderBy('name')->limit(8)->get();
    @endphp

    {{-- Mobile backdrop --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-zinc-900/40 lg:hidden" @click="sidebar = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-white border-r border-zinc-200 transition-transform duration-200 lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="h-16 flex items-center justify-between px-5 border-b border-zinc-100">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="grid place-items-center w-8 h-8 rounded-lg bg-brand-600 text-white"><x-icon name="brain" class="w-5 h-5" /></span>
                <span class="font-semibold text-zinc-900 tracking-tight">Second Brain</span>
            </a>
            <button class="lg:hidden btn-ghost p-1.5" @click="sidebar = false" aria-label="Close menu"><x-icon name="x" /></button>
        </div>

        <div class="p-4">
            <a href="{{ route('notes.create') }}" class="btn-primary w-full"><x-icon name="plus" class="w-4 h-4" /> New note</a>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 space-y-0.5">
            @foreach ($nav as [$route, $label, $icon, $active])
                <a href="{{ route($route) }}" @class(['nav-item', 'nav-item-active' => request()->routeIs($active) && ! request('trash') && ! request('favorites')])>
                    <x-icon :name="$icon" /> {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('notes.index', ['favorites' => 1]) }}" @class(['nav-item', 'nav-item-active' => request('favorites')])><x-icon name="star" /> Favorites</a>
            <a href="{{ route('notes.index', ['trash' => 1]) }}" @class(['nav-item', 'nav-item-active' => request('trash')])><x-icon name="trash" /> Trash</a>

            @if ($categories->isNotEmpty())
                <div class="pt-6 pb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Categories</div>
                @foreach ($categories as $c)
                    <a href="{{ route('notes.index', ['category' => $c->id]) }}" @class(['nav-item py-1.5', 'nav-item-active' => request('category') == $c->id])>
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500/70 mx-1.5"></span>
                        <span class="flex-1 truncate">{{ $c->name }}</span>
                        <span class="text-xs text-zinc-400">{{ $c->notes_count }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="border-t border-zinc-100 p-3 space-y-0.5">
            <a href="{{ route('settings.ai') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('settings.*')])><x-icon name="cog" /> AI Settings</a>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="nav-item w-full">
                    <span class="grid place-items-center w-7 h-7 rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span class="flex-1 text-left truncate">{{ $user->name }}</span>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute bottom-full left-0 right-0 mb-1 card p-1">
                    <a href="{{ route('profile.edit') }}" class="nav-item"><x-icon name="user" class="w-4 h-4" /> Profile</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="nav-item w-full"><x-icon name="logout" class="w-4 h-4" /> Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="lg:pl-64 min-h-full flex flex-col">
        <header class="sticky top-0 z-20 h-16 flex items-center gap-3 px-4 sm:px-6 lg:px-8 bg-zinc-50/80 backdrop-blur border-b border-zinc-200/70">
            <button class="lg:hidden btn-ghost p-1.5 -ml-1.5" @click="sidebar = true" aria-label="Open menu"><x-icon name="menu" /></button>
            @if (request()->routeIs('notes.index'))
                <div class="flex-1"></div>
            @else
            <form action="{{ route('notes.index') }}" method="GET" class="flex-1 max-w-xl relative">
                <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                <input name="q" value="{{ request()->routeIs('notes.index') ? request('q') : '' }}" placeholder="Search your notes by meaning or keyword…" class="input pl-9 bg-white" data-search>
                <kbd class="hidden sm:block absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-zinc-400 border border-zinc-200 rounded px-1.5 py-0.5">/</kbd>
            </form>
            @endif
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6">
            @isset($header)<div class="mb-6">{{ $header }}</div>@endisset

            @foreach (['status' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'error' => 'bg-red-50 text-red-800 border-red-200'] as $key => $cls)
                @if (is_string(session($key)) && ! in_array(session($key), ['profile-updated', 'password-updated', 'verification-link-sent']))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                         class="mb-4 flex items-start justify-between gap-3 border rounded-lg px-4 py-2.5 text-sm {{ $cls }}">
                        <span>{{ session($key) }}</span>
                        <button @click="show = false" class="opacity-60 hover:opacity-100"><x-icon name="x" class="w-4 h-4" /></button>
                    </div>
                @endif
            @endforeach

            {{ $slot }}
        </main>
    </div>
</body>
</html>
