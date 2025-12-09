@extends('ui::layouts.guest')

@section('title', '403 - Forbidden')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">
        <h1 class="text-6xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">403</h1>
        <h2 class="text-2xl font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-4">Forbidden</h2>
        <p class="text-[#706f6c] dark:text-[#A1A09A] mb-8">
            {{ $exception->getMessage() ?: 'You do not have permission to access this resource.' }}
        </p>

        @auth
            @php
                $debugService = app(\Modules\AuthorizationDebug\Services\AuthorizationDebugService::class);
                $lastFailure = $debugService->getLastFailureForUser(auth()->user());
            @endphp

            @if($lastFailure)
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                    <p class="text-blue-800 dark:text-blue-200 mb-2">
                        An authorization failure was recently logged for your account.
                    </p>
                    <a href="{{ route('authorization-debug.su53') }}" class="inline-block px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-sm hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">
                        Analyze Last Authorization Failure (SU53)
                    </a>
                </div>
            @endif

            <div class="flex gap-4 justify-center">
                <a href="{{ route('dashboard') }}" class="inline-block px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    Go to Dashboard
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline-block">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-500 text-white rounded-sm hover:bg-red-700 dark:hover:bg-red-600 transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="inline-block px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Login
            </a>
        @endauth
    </div>
</div>
@endsection

