@extends('ui::layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-authentication::label for="email" :value="__('Email')" />
            <x-authentication::input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-authentication::input-error :name="'email'" />
        </div>

        <!-- Password -->
        <div>
            <x-authentication::label for="password" :value="__('Password')" />
            <x-authentication::input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-authentication::input-error :name="'password'" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-authentication::label for="password_confirmation" :value="__('Confirm Password')" />
            <x-authentication::input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-authentication::input-error :name="'password_confirmation'" />
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>
@endsection

