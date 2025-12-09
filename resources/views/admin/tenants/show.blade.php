@extends('authorization::admin.layout')

@section('title', 'Tenant: ' . $tenant->name)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Tenant: {{ $tenant->name }}</h1>
        <div class="flex gap-4">
            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
                Edit
            </a>
            <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
                Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm p-6 space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">ID</label>
                <p class="font-medium">{{ $tenant->id }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Name</label>
                <p>{{ $tenant->name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Domain</label>
                <p>{{ $tenant->domain ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Subdomain</label>
                <p>{{ $tenant->subdomain ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Status</label>
                <span class="px-2 py-1 text-xs rounded-full 
                    @if($tenant->status === 'active') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                    @elseif($tenant->status === 'suspended') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                    @else bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                    @endif">
                    {{ ucfirst($tenant->status) }}
                </span>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Plan</label>
                <p>{{ ucfirst($tenant->plan) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Max Users</label>
                <p>{{ $tenant->max_users }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Created At</label>
                <p>{{ $tenant->created_at->format('Y-m-d H:i:s') }}</p>
            </div>
            @if($tenant->expires_at)
                <div>
                    <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Expires At</label>
                    <p>{{ $tenant->expires_at->format('Y-m-d H:i:s') }}</p>
                </div>
            @endif
        </div>

        <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" class="pt-4 border-t border-[#e3e3e0] dark:border-[#3E3E3A]">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-sm hover:bg-red-700 transition-colors" onclick="return confirm('Are you sure? This will delete all tenant data!')">
                Delete Tenant
            </button>
        </form>
    </div>
</div>
@endsection

