<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Second Brain</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans">
    <div class="min-h-full grid lg:grid-cols-2">
        <div class="hidden lg:flex flex-col justify-between p-12 bg-gradient-to-br from-brand-600 via-brand-700 to-violet-700 text-white">
            <a href="/" class="flex items-center gap-2.5 font-semibold"><x-application-logo class="w-9 h-9 bg-white/15" /> Second Brain</a>
            <div class="max-w-md">
                <h2 class="text-3xl font-semibold tracking-tight leading-tight">Capture everything.<br>Find it by meaning.<br>Ask it anything.</h2>
                <p class="mt-4 text-white/75">A private knowledge base with an AI assistant that answers from your own notes and shows its sources.</p>
            </div>
            <p class="text-xs text-white/50">University project · Laravel</p>
        </div>
        <div class="flex flex-col justify-center px-6 py-12 sm:px-12">
            <div class="w-full max-w-sm mx-auto">
                <a href="/" class="lg:hidden flex items-center gap-2.5 font-semibold text-zinc-900 mb-8"><x-application-logo class="w-9 h-9" /> Second Brain</a>
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
