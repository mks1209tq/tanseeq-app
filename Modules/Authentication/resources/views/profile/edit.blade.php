@extends('ui::layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-3xl font-bold mb-6">Profile</h1>

        <!-- Update Profile Information -->
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm mb-6">
            <h2 class="text-xl font-semibold mb-4">Profile Information</h2>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')

                <!-- Name -->
                <div>
                    <x-authentication::label for="name" :value="__('Name')" />
                    <x-authentication::input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                    <x-authentication::input-error :name="'name'" />
                </div>

                <!-- Email -->
                <div>
                    <x-authentication::label for="email" :value="__('Email')" />
                    <x-authentication::input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                    <x-authentication::input-error :name="'email'" />

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div>
                            <p class="text-sm mt-2 text-[#706f6c] dark:text-[#A1A09A]">
                                {{ __('Your email address is unverified.') }}

                                <button form="send-verification" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] underline">
                                    {{ __('Click here to re-send the verification email.') }}
                                </button>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                        {{ __('Save') }}
                    </button>

                    @if (session('status') === 'profile-updated')
                        <p class="text-sm text-green-600 dark:text-green-400">
                            {{ __('Saved.') }}
                        </p>
                    @endif
                </div>
            </form>
        </div>

        <!-- Update Password -->
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm mb-6">
            <h2 class="text-xl font-semibold mb-4">Update Password</h2>

            <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
                @csrf
                @method('put')

                <!-- Current Password -->
                <div>
                    <x-authentication::label for="current_password" :value="__('Current Password')" />
                    <x-authentication::input id="current_password" name="current_password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
                    <x-authentication::input-error :name="'current_password'" />
                </div>

                <!-- New Password -->
                <div>
                    <x-authentication::label for="password" :value="__('New Password')" />
                    <x-authentication::input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    <x-authentication::input-error :name="'password'" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-authentication::label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-authentication::input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    <x-authentication::input-error :name="'password_confirmation'" />
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                        {{ __('Update Password') }}
                    </button>

                    @if (session('status') === 'password-updated')
                        <p class="text-sm text-green-600 dark:text-green-400">
                            {{ __('Password updated successfully.') }}
                        </p>
                    @endif
                </div>
            </form>
        </div>

        <!-- Delete Account -->
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
            <h2 class="text-xl font-semibold mb-4 text-red-600 dark:text-red-400">Delete Account</h2>

            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-4">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                @csrf
                @method('delete')

                <div class="mb-4">
                    <x-authentication::label for="password" :value="__('Password')" />
                    <x-authentication::input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="{{ __('Password') }}" />
                    <x-authentication::input-error :name="'password'" />
                </div>

                <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white rounded-sm hover:bg-red-700 dark:hover:bg-red-800 transition-colors">
                    {{ __('Delete Account') }}
                </button>
            </form>
        </div>
    </div>

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
            @csrf
        </form>
    @endif
@endsection

