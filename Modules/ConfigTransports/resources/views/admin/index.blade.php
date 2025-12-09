@extends('authorization::admin.layout')

@section('title', 'Transport Requests')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Transport Requests</h1>
        <a href="{{ route('admin.transports.create') }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
            + New Transport Request
        </a>
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
            <thead class="bg-gray-50 dark:bg-[#1C1C1A]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Created At</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                @forelse($transports as $transport)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $transport->number }}</td>
                        <td class="px-6 py-4">{{ ucfirst($transport->type) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                @if($transport->status === 'open') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                @elseif($transport->status === 'released') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                @elseif($transport->status === 'exported') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                @else bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200
                                @endif">
                                {{ ucfirst($transport->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ Str::limit($transport->description, 50) }}</td>
                        <td class="px-6 py-4">{{ $transport->creator->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">{{ $transport->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.transports.show', $transport) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-[#706f6c] dark:text-[#A1A09A]">No transport requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $transports->links() }}
</div>
@endsection

