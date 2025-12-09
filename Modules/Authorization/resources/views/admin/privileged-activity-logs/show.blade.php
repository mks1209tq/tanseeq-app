@extends('ui::layouts.app')

@section('title', 'Privileged Activity Log Details')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
                <h1 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Privileged Activity Log Details</h1>
            </div>
            <a href="{{ route('admin.authorization.privileged-activity-logs.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                ← Back to Logs
            </a>
        </div>

        <div class="bg-white dark:bg-[#1C1C1A] rounded-lg border border-[#19140035] dark:border-[#3E3E3A] shadow-sm">
            <div class="p-6 space-y-6">
                <!-- User Information -->
                <div>
                    <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">User Information</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">User</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->user->name ?? 'Unknown' }} ({{ $privilegedActivityLog->user->email ?? 'N/A' }})
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Role Type</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($privilegedActivityLog->role_type === 'SuperAdmin')
                                        bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200
                                    @else
                                        bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200
                                    @endif">
                                    {{ $privilegedActivityLog->role_type }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Authorization Information -->
                <div>
                    <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Authorization Information</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Authorization Object</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->auth_object_code }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Activity Code</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->activity_code ?? 'N/A' }}
                            </dd>
                        </div>
                        @if($privilegedActivityLog->required_fields)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Required Fields</dt>
                                <dd class="mt-1">
                                    <pre class="bg-[#F5F5F4] dark:bg-[#2A2A28] p-3 rounded text-xs text-[#1b1b18] dark:text-[#EDEDEC] overflow-x-auto">{{ json_encode($privilegedActivityLog->required_fields, JSON_PRETTY_PRINT) }}</pre>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Request Information -->
                <div>
                    <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Request Information</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Route Name</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->route_name ?? 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Request Path</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->request_path }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">HTTP Method</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded
                                    @if($privilegedActivityLog->request_method === 'GET' || $privilegedActivityLog->request_method === 'HEAD')
                                        bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200
                                    @else
                                        bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-200
                                    @endif">
                                    {{ $privilegedActivityLog->request_method }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">IP Address</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->client_ip }}
                            </dd>
                        </div>
                        @if($privilegedActivityLog->user_agent)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">User Agent</dt>
                                <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                    {{ $privilegedActivityLog->user_agent }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Request Data (if available) -->
                @if($privilegedActivityLog->request_data)
                    <div>
                        <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Request Data</h2>
                        <pre class="bg-[#F5F5F4] dark:bg-[#2A2A28] p-3 rounded text-xs text-[#1b1b18] dark:text-[#EDEDEC] overflow-x-auto">{{ json_encode($privilegedActivityLog->request_data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif

                <!-- Notes -->
                @if($privilegedActivityLog->notes)
                    <div>
                        <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Notes</h2>
                        <p class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]">{{ $privilegedActivityLog->notes }}</p>
                    </div>
                @endif

                <!-- Timestamp -->
                <div>
                    <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Timestamp</h2>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Created At</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->created_at->format('Y-m-d H:i:s') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Updated At</dt>
                            <dd class="mt-1 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ $privilegedActivityLog->updated_at->format('Y-m-d H:i:s') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

