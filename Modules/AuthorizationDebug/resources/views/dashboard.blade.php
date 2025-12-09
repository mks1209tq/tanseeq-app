@extends('ui::layouts.app')

@section('title', 'Authorization Debug Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Authorization Debug Dashboard</h1>
    </div>

    <!-- Statistics Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- All Time Statistics -->
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
            <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">All Time</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Total Checks:</span>
                    <span class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">{{ number_format($userStats['all_time']['total']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Successes:</span>
                    <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ number_format($userStats['all_time']['successes']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Failures:</span>
                    <span class="text-lg font-semibold text-red-600 dark:text-red-400">{{ number_format($userStats['all_time']['failures']) }}</span>
                </div>
                <div class="pt-2 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Success Rate:</span>
                        <span class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">{{ $userStats['all_time']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last 24 Hours -->
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
            <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">Last 24 Hours</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Total Checks:</span>
                    <span class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">{{ number_format($userStats['last_24_hours']['total']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Successes:</span>
                    <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ number_format($userStats['last_24_hours']['successes']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Failures:</span>
                    <span class="text-lg font-semibold text-red-600 dark:text-red-400">{{ number_format($userStats['last_24_hours']['failures']) }}</span>
                </div>
                <div class="pt-2 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Success Rate:</span>
                        <span class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">{{ $userStats['last_24_hours']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last 7 Days -->
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
            <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">Last 7 Days</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Total Checks:</span>
                    <span class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">{{ number_format($userStats['last_7_days']['total']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Successes:</span>
                    <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ number_format($userStats['last_7_days']['successes']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Failures:</span>
                    <span class="text-lg font-semibold text-red-600 dark:text-red-400">{{ number_format($userStats['last_7_days']['failures']) }}</span>
                </div>
                <div class="pt-2 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Success Rate:</span>
                        <span class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">{{ $userStats['last_7_days']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last 30 Days -->
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
            <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">Last 30 Days</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Total Checks:</span>
                    <span class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">{{ number_format($userStats['last_30_days']['total']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Successes:</span>
                    <span class="text-lg font-semibold text-green-600 dark:text-green-400">{{ number_format($userStats['last_30_days']['successes']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Failures:</span>
                    <span class="text-lg font-semibold text-red-600 dark:text-red-400">{{ number_format($userStats['last_30_days']['failures']) }}</span>
                </div>
                <div class="pt-2 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Success Rate:</span>
                        <span class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">{{ $userStats['last_30_days']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Statistics (Admin Only) -->
    @if($globalStats)
    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Global Statistics (All Users)</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">All Time</h3>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Total:</span>
                        <span class="font-semibold">{{ number_format($globalStats['all_time']['total']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Success:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($globalStats['all_time']['successes']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Fail:</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($globalStats['all_time']['failures']) }}</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                        <span class="font-medium">Rate:</span>
                        <span class="font-bold">{{ $globalStats['all_time']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">Last 24 Hours</h3>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Total:</span>
                        <span class="font-semibold">{{ number_format($globalStats['last_24_hours']['total']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Success:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($globalStats['last_24_hours']['successes']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Fail:</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($globalStats['last_24_hours']['failures']) }}</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                        <span class="font-medium">Rate:</span>
                        <span class="font-bold">{{ $globalStats['last_24_hours']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">Last 7 Days</h3>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Total:</span>
                        <span class="font-semibold">{{ number_format($globalStats['last_7_days']['total']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Success:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($globalStats['last_7_days']['successes']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Fail:</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($globalStats['last_7_days']['failures']) }}</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                        <span class="font-medium">Rate:</span>
                        <span class="font-bold">{{ $globalStats['last_7_days']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-2">Last 30 Days</h3>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Total:</span>
                        <span class="font-semibold">{{ number_format($globalStats['last_30_days']['total']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Success:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($globalStats['last_30_days']['successes']) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">Fail:</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($globalStats['last_30_days']['failures']) }}</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                        <span class="font-medium">Rate:</span>
                        <span class="font-bold">{{ $globalStats['last_30_days']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Test Authorization Check Card -->
        <a href="{{ route('authorization-debug.test-check') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Test Authorization Check</h3>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Perform a test authorization check to generate statistics.</p>
        </a>

        <!-- SU53 Debug View Card -->
        <a href="{{ route('authorization-debug.su53') }}" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">SU53 Debug View</h3>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">View your last authorization failure analysis (SU53-style).</p>
        </a>

        <!-- Admin: View Other Users -->
        @if(Auth::user()->isSuperAdmin())
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
            <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Admin Tools</h3>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-4">View authorization failures for other users.</p>
            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Use: <code class="bg-gray-100 dark:bg-[#1C1C1A] px-2 py-1 rounded">/auth/su53/{user_id}</code></p>
        </div>
        @endif
    </div>

    <!-- Information Section -->
    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">About Authorization Debug</h2>
        <div class="space-y-3 text-sm text-[#706f6c] dark:text-[#A1A09A]">
            <p>
                The Authorization Debug module provides SU53-like functionality for analyzing authorization failures. 
                Every authorization check (both successful and failed) is logged to help troubleshoot access issues.
            </p>
            <p>
                <strong>Features:</strong>
            </p>
            <ul class="list-disc list-inside space-y-1 ml-4">
                <li>View last authorization failure with detailed field-level analysis</li>
                <li>Compare required values vs. user's allowed values</li>
                <li>See match status for each authorization field</li>
                <li>View request context (route, path, method, IP)</li>
                <li>Admin access to view other users' failures</li>
            </ul>
        </div>
    </div>
</div>
@endsection

