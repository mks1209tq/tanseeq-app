@extends('ui::layouts.app')

@section('title', 'Role Authorizations')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Authorizations for Role: {{ $role->name }}</h1>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">{{ $role->description }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.authorization.role-authorizations.create', $role) }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                + New Authorization
            </a>
            <a href="{{ route('admin.authorization.roles.edit', $role) }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors">
                Back to Role
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm overflow-hidden">
        <div class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
            @forelse($role->roleAuthorizations as $authorization)
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-[#1C1C1A] transition-colors">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="font-semibold text-lg">{{ $authorization->authObject->code }}</h3>
                                @if($authorization->label)
                                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">({{ $authorization->label }})</span>
                                @endif
                            </div>
                            @if($authorization->authObject->description)
                                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-3">{{ $authorization->authObject->description }}</p>
                            @endif
                            @if($authorization->fields->count() > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($authorization->fields as $field)
                                        <span class="inline-flex items-center px-3 py-1 rounded-sm text-sm bg-gray-100 dark:bg-[#1C1C1A] border border-[#e3e3e0] dark:border-[#3E3E3A]">
                                            <span class="font-medium">{{ $field->field_code }}</span>
                                            <span class="mx-2 text-[#706f6c] dark:text-[#A1A09A]">{{ $field->operator }}</span>
                                            <span class="text-[#706f6c] dark:text-[#A1A09A]">
                                                @if($field->operator === '*')
                                                    *
                                                @elseif($field->operator === 'between')
                                                    {{ $field->value_from ?? '' }} - {{ $field->value_to ?? '' }}
                                                @else
                                                    {{ $field->value_from ?? '' }}
                                                @endif
                                            </span>
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] italic">No field rules defined</p>
                            @endif
                        </div>
                        <div class="flex gap-2 ml-4">
                            <a href="{{ route('admin.authorization.role-authorizations.edit', [$role, $authorization]) }}" class="px-3 py-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 hover:underline">
                                Edit
                            </a>
                            <form action="{{ route('admin.authorization.role-authorizations.destroy', [$role, $authorization]) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 hover:underline" onclick="return confirm('Are you sure you want to delete this authorization?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">No authorizations found for this role.</p>
                    <a href="{{ route('admin.authorization.role-authorizations.create', $role) }}" class="inline-block px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                        Create First Authorization
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

