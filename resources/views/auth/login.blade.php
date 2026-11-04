<x-guest-layout>
    <div class="eyebrow text-brand-600">AUTH / SIGN IN</div>
    <h1 class="mt-2 text-3xl font-semibold text-zinc-900 tracking-tight">Welcome back</h1>
    <p class="mt-1 mb-8 text-sm text-zinc-500">Sign in to your second brain.</p>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded-none border-zinc-400 text-brand-500 focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-zinc-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="font-mono text-[11px] text-zinc-500 hover:text-brand-600" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3 w-full sm:w-auto">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
<p class="mt-6 text-center text-sm text-zinc-500">No account? <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-700">Create one</a></p>
</x-guest-layout>
