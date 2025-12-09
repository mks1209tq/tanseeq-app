@extends('ui::layouts.app')

@section('title', 'Add Clipboard Item')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('clipboard.index') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Clipboard Manager</a>
        <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Add Clipboard Item</h1>
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
        <form action="{{ route('clipboard.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium mb-1">Title (optional)</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                @error('title')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="content" class="block text-sm font-medium mb-1">Content *</label>
                <textarea name="content" id="content" rows="6" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">{{ old('content') }}</textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium mb-1">Type</label>
                <select name="type" id="type" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                    <option value="text" {{ old('type', 'text') === 'text' ? 'selected' : '' }}>Text</option>
                    <option value="url" {{ old('type') === 'url' ? 'selected' : '' }}>URL</option>
                    <option value="code" {{ old('type') === 'code' ? 'selected' : '' }}>Code</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    Add to Clipboard
                </button>
                <a href="{{ route('clipboard.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors inline-block">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

