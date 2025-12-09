@extends('ui::layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <div class="mb-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <x-authentication::auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-authentication::label for="email" :value="__('Email')" />
            <x-authentication::input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-authentication::input-error :name="'email'" />
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                {{ __('Email Password Reset Link') }}
            </button>
        </div>
    </form>
@endsection

