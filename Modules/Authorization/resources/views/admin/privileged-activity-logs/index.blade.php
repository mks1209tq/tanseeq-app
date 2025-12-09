@extends('ui::layouts.app')

@section('title', 'Privileged Activity Logs')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
                <h1 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Privileged Activity Logs</h1>
            </div>
        </div>

        <div class="bg-white dark:bg-[#1C1C1A] rounded-lg border border-[#19140035] dark:border-[#3E3E3A] shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#F5F5F4] dark:bg-[#2A2A28] border-b border-[#19140035] dark:border-[#3E3E3A]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Role Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Object</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Route</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#19140035] dark:divide-[#3E3E3A]">
                        @forelse($logs as $log)
                            <tr class="hover:bg-[#F5F5F4] dark:hover:bg-[#2A2A28]">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ $log->user->name ?? 'Unknown' }}
                                    </div>
                                    <div class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                        {{ $log->user->email ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($log->role_type === 'SuperAdmin')
                                            bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200
                                        @else
                                            bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200
                                        @endif">
                                        {{ $log->role_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                    {{ $log->auth_object_code }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                    {{ $log->activity_code ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    {{ $log->route_name ?? $log->request_path }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded
                                        @if($log->request_method === 'GET' || $log->request_method === 'HEAD')
                                            bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200
                                        @else
                                            bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-200
                                        @endif">
                                        {{ $log->request_method }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    {{ $log->client_ip }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('admin.authorization.privileged-activity-logs.show', $log) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-4 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    No privileged activity logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-[#19140035] dark:border-[#3E3E3A]">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection

