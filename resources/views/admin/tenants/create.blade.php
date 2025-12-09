@extends('authorization::admin.layout')

@section('title', 'Create Tenant')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Create Tenant</h1>
        <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
            Back
        </a>
    </div>

    <form action="{{ route('admin.tenants.store') }}" method="POST" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium mb-2">Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#1C1C1A]">
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="domain" class="block text-sm font-medium mb-2">Domain</label>
            <input type="text" name="domain" id="domain" value="{{ old('domain') }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#1C1C1A]">
            @error('domain')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="subdomain" class="block text-sm font-medium mb-2">Subdomain</label>
            <input type="text" name="subdomain" id="subdomain" value="{{ old('subdomain') }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#1C1C1A]">
            @error('subdomain')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="plan" class="block text-sm font-medium mb-2">Plan *</label>
            <select name="plan" id="plan" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#1C1C1A]">
                <option value="basic">Basic</option>
                <option value="premium">Premium</option>
                <option value="enterprise">Enterprise</option>
            </select>
            @error('plan')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="max_users" class="block text-sm font-medium mb-2">Max Users *</label>
            <input type="number" name="max_users" id="max_users" value="{{ old('max_users', 10) }}" min="1" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#1C1C1A]">
            @error('max_users')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Create
            </button>
        </div>
    </form>
</div>
@endsection

