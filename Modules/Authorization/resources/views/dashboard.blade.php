@extends('ui::layouts.app')

@section('title', 'Authorization Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold">Authorization Dashboard</h1>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Auth Objects Card -->
            <a href="{{ route('admin.authorization.auth-objects.index') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Authorization Objects</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Manage authorization objects and their fields.</p>
            </a>

            <!-- Roles Card -->
            <a href="{{ route('admin.authorization.roles.index') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Roles</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Manage user roles and their permissions.</p>
            </a>

            <!-- User Roles Card -->
            @if(Auth::user()->isSuperAdmin())
            <a href="{{ route('admin.authorization.users.edit-roles', Auth::user()) }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">User Roles</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Assign roles to users.</p>
            </a>
            @endif

            <!-- Privileged Activity Logs Card -->
            @if(Auth::user()->hasAuthObject('AUTHORIZATION_DEBUG', []))
            <a href="{{ route('admin.authorization.privileged-activity-logs.index') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Privileged Activity Logs</h3>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">View activity logs for SuperAdmin and SuperReadOnly users.</p>
            </a>
            @endif
        </div>
    </div>
@endsection

