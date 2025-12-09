@extends('ui::layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Edit Role: {{ $role->name }}</h1>
        </div>
        <a href="{{ route('admin.authorization.role-authorizations.create', $role) }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
            + New Authorization
        </a>
    </div>

    <form action="{{ route('admin.authorization.roles.update', $role) }}" method="POST" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium mb-1">Name *</label>
            <input type="text" name="name" id="name" required value="{{ old('name', $role->name) }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">{{ old('description', $role->description) }}</textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Update
            </button>
            <a href="{{ route('admin.authorization.roles.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors">
                Cancel
            </a>
        </div>
    </form>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-[#e3e3e0] dark:border-[#3E3E3A]">
            <h2 class="text-xl font-semibold">Role Authorizations</h2>
        </div>
        <div class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
            @forelse($role->roleAuthorizations as $authorization)
                <div class="px-6 py-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium">{{ $authorization->authObject->code }}</h3>
                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $authorization->label }}</p>
                            <div class="mt-2 text-sm">
                                @foreach($authorization->fields as $field)
                                    <span class="inline-block mr-2 px-2 py-1 bg-gray-100 dark:bg-[#1C1C1A] rounded">
                                        {{ $field->field_code }}: {{ $field->operator }} {{ $field->value_from ?? '*' }} {{ $field->value_to ? '- ' . $field->value_to : '' }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.authorization.role-authorizations.edit', [$role, $authorization]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">Edit</a>
                            <form action="{{ route('admin.authorization.role-authorizations.destroy', [$role, $authorization]) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-4 text-center text-[#706f6c] dark:text-[#A1A09A]">No authorizations found.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

