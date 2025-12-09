@extends('authorization::admin.layout')

@section('title', 'Create Transport Request')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Create Transport Request</h1>
        <a href="{{ route('admin.transports.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
            Back
        </a>
    </div>

    <form action="{{ route('admin.transports.store') }}" method="POST" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm p-6 space-y-6">
        @csrf

        <div>
            <label for="type" class="block text-sm font-medium mb-2">Type</label>
            <select name="type" id="type" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#1C1C1A]">
                <option value="security">Security</option>
                <option value="config">Config</option>
                <option value="master_data">Master Data</option>
                <option value="mixed">Mixed</option>
            </select>
            @error('type')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium mb-2">Description</label>
            <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#1C1C1A]">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Target Environments</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" name="target_environments[]" value="qa" checked class="mr-2">
                    <span>QA</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="target_environments[]" value="prod" checked class="mr-2">
                    <span>Production</span>
                </label>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.transports.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Create
            </button>
        </div>
    </form>
</div>
@endsection

