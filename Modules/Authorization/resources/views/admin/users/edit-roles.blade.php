@extends('ui::layouts.app')

@section('title', 'Edit User Roles')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
        <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Edit Roles for: {{ $user->name }}</h1>
    </div>

    <form action="{{ route('admin.authorization.users.update-roles', $user) }}" method="POST" class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-2">Roles</label>
            <div class="space-y-2">
                @foreach($roles as $role)
                    <div class="flex items-center">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ $user->roles->contains($role->id) ? 'checked' : '' }} class="rounded">
                        <label for="role_{{ $role->id }}" class="ml-2 text-sm">{{ $role->name }}</label>
                        @if($role->description)
                            <span class="ml-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">- {{ $role->description }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Update Roles
            </button>
            <a href="{{ route('admin.authorization.roles.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

