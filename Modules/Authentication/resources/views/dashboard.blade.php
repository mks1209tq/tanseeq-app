@extends('ui::layouts.app')

@section('title', 'Authentication Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold">Authentication Dashboard</h1>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Settings Card -->
            <a href="{{ route('authentication.settings.edit') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Settings</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Manage authentication settings including email verification, 2FA, and password requirements.</p>
            </a>

            <!-- User Management Card -->
            @if(Auth::user()->isSuperAdmin())
            <a href="{{ route('admin.users.create') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Create User</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Create a new user account.</p>
            </a>
            @endif

            <!-- Profile Card -->
            <a href="{{ route('profile.edit') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Profile</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">View and edit your profile information.</p>
            </a>
        </div>
    </div>
@endsection

