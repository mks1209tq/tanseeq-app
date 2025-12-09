@extends('ui::layouts.app')

@section('title', 'Create Auth Object')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
        <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Create Authorization Object</h1>
    </div>

    <form action="{{ route('admin.authorization.auth-objects.store') }}" method="POST" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm space-y-6">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium mb-1">Code *</label>
            <input type="text" name="code" id="code" required value="{{ old('code') }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">{{ old('description') }}</textarea>
        </div>

        <div>
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium">Fields</label>
                <button type="button" onclick="addField()" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">+ Add Field</button>
            </div>
            <div id="fields-container" class="space-y-4">
                <!-- Fields will be added here dynamically -->
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Create
            </button>
            <a href="{{ route('admin.authorization.auth-objects.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
let fieldIndex = 0;
function addField() {
    const container = document.getElementById('fields-container');
    const fieldHtml = `
        <div class="border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm p-4 space-y-3">
            <div class="flex justify-between">
                <h4 class="font-medium">Field ${fieldIndex + 1}</h4>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-600 dark:text-red-400">Remove</button>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Code *</label>
                <input type="text" name="fields[${fieldIndex}][code]" required class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Label</label>
                <input type="text" name="fields[${fieldIndex}][label]" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="fields[${fieldIndex}][is_org_level]" value="1" class="rounded">
                <label class="text-sm">Organization Level</label>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Sort</label>
                <input type="number" name="fields[${fieldIndex}][sort]" value="0" min="0" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615]">
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', fieldHtml);
    fieldIndex++;
}
</script>
@endsection

