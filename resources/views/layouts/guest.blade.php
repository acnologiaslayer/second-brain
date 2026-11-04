<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Second Brain</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans">
    <div class="min-h-full grid lg:grid-cols-[1.1fr_1fr]">
        <div class="hidden lg:flex flex-col justify-between p-12 bg-zinc-900 text-zinc-50 relative overflow-hidden">
            <div class="absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>
            <div class="relative">
                <div class="font-mono text-[15px] font-bold tracking-[0.16em]"><span class="text-brand-500">◆</span> SECOND BRAIN</div>
                <div class="mt-1 font-mono text-[9.5px] tracking-[0.24em] text-zinc-500">KNOWLEDGE CONSOLE · v1</div>
            </div>
            <div class="relative max-w-lg">
                <div class="font-mono text-[11px] tracking-label text-brand-500">// WHAT IT DOES</div>
                <h2 class="mt-4 text-4xl font-semibold tracking-tight leading-[1.1]">Capture everything.<br>Find it by meaning.<br><span class="text-brand-500">Ask it anything.</span></h2>
                <div class="mt-8 font-mono text-[12px] text-zinc-400 space-y-1.5">
                    <div><span class="text-brand-500">›</span> notes, categories, tags</div>
                    <div><span class="text-brand-500">›</span> semantic + keyword search</div>
                    <div><span class="text-brand-500">›</span> answers cite your own notes</div>
                    <div><span class="text-brand-500">›</span> runs on local or cloud models</div>
                </div>
            </div>
            <div class="relative flex items-center gap-2 font-mono text-[10px] tracking-wider text-zinc-500">
                <span class="led bg-ok led-pulse"></span> SYSTEM ONLINE · LARAVEL · {{ now()->format('Y') }}
            </div>
        </div>
        <div class="flex flex-col justify-center px-6 py-12 sm:px-12">
            <div class="w-full max-w-sm mx-auto">
                <div class="lg:hidden mb-10">
                    <div class="font-mono text-[14px] font-bold tracking-[0.16em] text-zinc-900"><span class="text-brand-500">◆</span> SECOND BRAIN</div>
                    <div class="mt-1 font-mono text-[9px] tracking-[0.24em] text-zinc-400">KNOWLEDGE CONSOLE · v1</div>
                </div>
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
