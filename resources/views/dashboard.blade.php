@extends('ui::layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold">Dashboard From Main level</h1>
            
            @if(Auth::user()->isSuperAdmin())
                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    Create New User
                </a>
            @endif
        </div>
        
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
            <p class="text-[#706f6c] dark:text-[#A1A09A] mb-6">
                Welcome, {{ Auth::user()->name }}! You are logged in.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Authentication Module Dashboard -->
                <a href="{{ route('authentication.dashboard') }}" class="block p-4 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    <h3 class="text-lg font-semibold mb-2">Authentication Module</h3>
                    <p class="text-sm opacity-90">Manage authentication settings, users, and profiles</p>
                </a>

                <!-- Authorization Module Dashboard -->
                <a href="{{ route('authorization.dashboard') }}" class="block p-4 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    <h3 class="text-lg font-semibold mb-2">Authorization Module</h3>
                    <p class="text-sm opacity-90">Manage roles, authorization objects, and permissions</p>
                </a>

                <!-- Authorization Debug Module Dashboard -->
                <a href="{{ route('authorization-debug.dashboard') }}" class="block p-4 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    <h3 class="text-lg font-semibold mb-2">Authorization Debug</h3>
                    <p class="text-sm opacity-90">View and analyze authorization failures (SU53-style)</p>
                </a>
            </div>
        </div>
    </div>
@endsection

