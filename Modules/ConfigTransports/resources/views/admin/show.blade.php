@extends('authorization::admin.layout')

@section('title', 'Transport Request: ' . $transportRequest->number)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Transport Request: {{ $transportRequest->number }}</h1>
        <a href="{{ route('admin.transports.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
            Back
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm p-6 space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Number</label>
                <p class="font-medium">{{ $transportRequest->number }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Type</label>
                <p>{{ ucfirst($transportRequest->type) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Status</label>
                <span class="px-2 py-1 text-xs rounded-full 
                    @if($transportRequest->status === 'open') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                    @elseif($transportRequest->status === 'released') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                    @elseif($transportRequest->status === 'exported') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                    @else bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200
                    @endif">
                    {{ ucfirst($transportRequest->status) }}
                </span>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Source Environment</label>
                <p>{{ strtoupper($transportRequest->source_environment) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Target Environments</label>
                <p>{{ implode(', ', array_map('strtoupper', $transportRequest->target_environments ?? [])) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Created By</label>
                <p>{{ $transportRequest->creator->name ?? 'N/A' }}</p>
            </div>
            @if($transportRequest->released_at)
                <div>
                    <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Released At</label>
                    <p>{{ $transportRequest->released_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Released By</label>
                    <p>{{ $transportRequest->releaser->name ?? 'N/A' }}</p>
                </div>
            @endif
        </div>

        @if($transportRequest->description)
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Description</label>
                <p>{{ $transportRequest->description }}</p>
            </div>
        @endif

        @if($transportRequest->canBeReleased())
            <form action="{{ route('admin.transports.release', $transportRequest) }}" method="POST" class="pt-4 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-sm hover:bg-blue-700 transition-colors">
                    Release Transport Request
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e3e3e0] dark:border-[#3E3E3A]">
            <h2 class="text-xl font-semibold">Transport Items ({{ $transportRequest->items->count() }})</h2>
        </div>
        <table class="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
            <thead class="bg-gray-50 dark:bg-[#1C1C1A]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Object Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Identifier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Operation</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Recorded At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                @forelse($transportRequest->items as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->object_type }}</td>
                        <td class="px-6 py-4">
                            @if(is_array($item->identifier))
                                {{ json_encode($item->identifier) }}
                            @else
                                {{ $item->identifier }}
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                @if($item->operation === 'create') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                @elseif($item->operation === 'update') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                @else bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                @endif">
                                {{ ucfirst($item->operation) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $item->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-[#706f6c] dark:text-[#A1A09A]">No items in this transport request.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

