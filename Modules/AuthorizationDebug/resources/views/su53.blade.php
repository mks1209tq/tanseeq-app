@extends('ui::layouts.app')

@section('title', 'Authorization Check Analysis (SU53-style)')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Authorization Check Analysis (SU53-style)</h1>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
            Back to Dashboard
        </a>
    </div>

    @if($isViewingOtherUser)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <p class="text-blue-800 dark:text-blue-200">
                <strong>Viewing authorization failure for:</strong> {{ $user->name }} (ID: {{ $user->id }}, Email: {{ $user->email }})
            </p>
        </div>
    @endif

    @if($failure === null)
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm p-8">
            <div class="text-center">
                <p class="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                    No authorization failures have been logged for {{ $isViewingOtherUser ? 'this user' : 'your user' }}.
                </p>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm overflow-hidden">
            <!-- Header Information -->
            <div class="p-6 border-b border-[#e3e3e0] dark:border-[#3E3E3A]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">User</p>
                        <p class="font-medium">{{ $user->name }} (ID: {{ $user->id }})</p>
                    </div>
                    <div>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Timestamp</p>
                        <p class="font-medium">{{ $failure->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Authorization Object</p>
                        <p class="font-medium">{{ $failure->auth_object_code }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Result</p>
                        <p class="font-medium">
                            @if($failure->is_allowed)
                                <span class="text-green-600 dark:text-green-400">ALLOWED</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">DENIED</span>
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-2">Assigned Roles</p>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $userRoles = $user->roles()->pluck('name')->toArray();
                            @endphp
                            @if(empty($userRoles))
                                <span class="text-[#706f6c] dark:text-[#A1A09A] italic">No roles assigned</span>
                            @else
                                @foreach($userRoles as $roleName)
                                    <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded text-sm font-medium">
                                        {{ $roleName }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Context -->
            <div class="p-6 border-b border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h2 class="text-lg font-semibold mb-4">Request Context</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($failure->route_name)
                        <div>
                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Route Name</p>
                            <p class="font-medium">{{ $failure->route_name }}</p>
                        </div>
                    @endif
                    @if($failure->request_path)
                        <div>
                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Request Path</p>
                            <p class="font-medium">{{ $failure->request_path }}</p>
                        </div>
                    @endif
                    @if($failure->request_method)
                        <div>
                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">HTTP Method</p>
                            <p class="font-medium">{{ $failure->request_method }}</p>
                        </div>
                    @endif
                    @if($failure->client_ip)
                        <div>
                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Client IP</p>
                            <p class="font-medium">{{ $failure->client_ip }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Required Fields Analysis -->
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Field-Level Analysis</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                        <thead class="bg-gray-50 dark:bg-[#1C1C1A]">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Field Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Required Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">User's Allowed Values</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                            @foreach($failure->required_fields as $fieldCode => $requiredValue)
                                @php
                                    $summary = $failure->summary ?? [];
                                    $fieldSummary = $summary[$fieldCode] ?? null;
                                    $matched = false;
                                    $allowedValues = [];

                                    if ($fieldSummary && isset($fieldSummary['rules'])) {
                                        foreach ($fieldSummary['rules'] as $rule) {
                                            $operator = $rule['operator'] ?? null;
                                            $values = $rule['values'] ?? [];

                                            if ($operator === '*') {
                                                $matched = true;
                                                $allowedValues[] = 'Any (wildcard)';
                                            } elseif ($operator === '=' && !empty($values)) {
                                                $allowedValues[] = $values[0];
                                                if ($values[0] === $requiredValue) {
                                                    $matched = true;
                                                }
                                            } elseif ($operator === 'in' && !empty($values)) {
                                                $allowedValues = array_merge($allowedValues, $values);
                                                if (in_array($requiredValue, $values, true)) {
                                                    $matched = true;
                                                }
                                            } elseif ($operator === 'between' && isset($values['from']) && isset($values['to'])) {
                                                $allowedValues[] = "{$values['from']} - {$values['to']}";
                                                if ($requiredValue >= $values['from'] && $requiredValue <= $values['to']) {
                                                    $matched = true;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $fieldCode }}</td>
                                    <td class="px-6 py-4">{{ $requiredValue }}</td>
                                    <td class="px-6 py-4">
                                        @if(empty($allowedValues))
                                            <span class="text-[#706f6c] dark:text-[#A1A09A]">No rules found</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach(array_unique($allowedValues) as $value)
                                                    <span class="inline-block px-2 py-1 bg-gray-100 dark:bg-[#1C1C1A] rounded text-sm">{{ $value }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($matched)
                                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 rounded text-sm font-medium">MATCHED</span>
                                        @else
                                            <span class="px-2 py-1 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 rounded text-sm font-medium">NOT MATCHED</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

