<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @foreach (['status' => 'bg-green-50 text-green-800 border-green-200', 'error' => 'bg-red-50 text-red-800 border-red-200'] as $key => $cls)
                    @if (session($key) && is_string(session($key)) && session($key) !== 'profile-updated' && session($key) !== 'password-updated')
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                            <div class="border rounded-md px-4 py-2 text-sm {{ $cls }}">{{ session($key) }}</div>
                        </div>
                    @endif
                @endforeach
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
