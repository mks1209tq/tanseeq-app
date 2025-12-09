@extends('ui::layouts.app')

@section('title', 'Authentication Settings')

@section('content')
<div class="max-w-4xl">
    <h1 class="text-3xl font-bold mb-6">Authentication Settings</h1>

    <form action="{{ route('authentication.settings.update') }}" method="POST" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <!-- Email Verification -->
        <div class="flex items-center justify-between p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg">
            <div>
                <h3 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Require Email Verification</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Require users to verify their email address before accessing the application</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="require_email_verification" value="1" {{ (isset($settings['require_email_verification']) && $settings['require_email_verification']->getAttributes()['value'] === '1') ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-black dark:peer-focus:ring-white rounded-full peer dark:bg-[#3E3E3A] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#1b1b18] dark:peer-checked:bg-[#eeeeec]"></div>
            </label>
        </div>

        <!-- Force Two-Factor Authentication -->
        <div class="flex items-center justify-between p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg">
            <div>
                <h3 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Force Two-Factor Authentication</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Force all users to enable two-factor authentication</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="force_two_factor" value="1" {{ (isset($settings['force_two_factor']) && $settings['force_two_factor']->getAttributes()['value'] === '1') ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-black dark:peer-focus:ring-white rounded-full peer dark:bg-[#3E3E3A] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#1b1b18] dark:peer-checked:bg-[#eeeeec]"></div>
            </label>
        </div>

        <!-- Allow Registration -->
        <div class="flex items-center justify-between p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg">
            <div>
                <h3 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Allow Registration</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Allow new users to register accounts</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="allow_registration" value="1" {{ (isset($settings['allow_registration']) && $settings['allow_registration']->getAttributes()['value'] === '1') ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-black dark:peer-focus:ring-white rounded-full peer dark:bg-[#3E3E3A] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#1b1b18] dark:peer-checked:bg-[#eeeeec]"></div>
            </label>
        </div>

        <!-- Password Minimum Length -->
        <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg">
            <label for="password_min_length" class="block text-sm font-medium mb-2">Password Minimum Length</label>
            <input type="number" name="password_min_length" id="password_min_length" value="{{ $settings['password_min_length']->value ?? '8' }}" min="6" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Minimum password length requirement</p>
        </div>

        <!-- Session Lifetime -->
        <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg">
            <label for="session_lifetime" class="block text-sm font-medium mb-2">Session Lifetime (minutes)</label>
            <input type="number" name="session_lifetime" id="session_lifetime" value="{{ $settings['session_lifetime']->value ?? '120' }}" min="1" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Session lifetime in minutes</p>
        </div>

        <!-- Max Login Attempts -->
        <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg">
            <label for="max_login_attempts" class="block text-sm font-medium mb-2">Max Login Attempts</label>
            <input type="number" name="max_login_attempts" id="max_login_attempts" value="{{ $settings['max_login_attempts']->value ?? '5' }}" min="1" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Maximum number of failed login attempts before lockout</p>
        </div>

        <!-- Lockout Duration -->
        <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg">
            <label for="lockout_duration" class="block text-sm font-medium mb-2">Lockout Duration (minutes)</label>
            <input type="number" name="lockout_duration" id="lockout_duration" value="{{ $settings['lockout_duration']->value ?? '60' }}" min="1" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Duration in minutes that a user is locked out after exceeding max login attempts</p>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection

