@extends('ui::layouts.guest')

@section('title', 'Login')

@section('content')
    <x-authentication::auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-authentication::label for="email" :value="__('Email')" />
            <x-authentication::input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-authentication::input-error :name="'email'" />
        </div>

        <!-- Password -->
        <div>
            <x-authentication::label for="password" :value="__('Password')" />
            <x-authentication::input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-authentication::input-error :name="'password'" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-[#e3e3e0] dark:border-[#3E3E3A] text-black dark:text-white focus:ring-black dark:focus:ring-white" name="remember">
            <label for="remember_me" class="ml-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                {{ __('Remember me') }}
            </label>
        </div>

        <div class="flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                {{ __('Log in') }}
            </button>
        </div>

    </form>
@endsection

