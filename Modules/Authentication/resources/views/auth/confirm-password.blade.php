@extends('ui::layouts.app')

@section('title', 'Confirm Password')

@section('content')
    <div class="mb-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <x-authentication::label for="password" :value="__('Password')" />
            <x-authentication::input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-authentication::input-error :name="'password'" />
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
@endsection

