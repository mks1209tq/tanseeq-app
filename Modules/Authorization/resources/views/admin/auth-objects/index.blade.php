@extends('ui::layouts.app')

@section('title', 'Auth Objects')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('authorization.dashboard') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Authorization Admin</a>
            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Authorization Objects</h1>
        </div>
        <a href="{{ route('admin.authorization.auth-objects.create') }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
            + New Auth Object
        </a>
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
            <thead class="bg-gray-50 dark:bg-[#1C1C1A]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Fields</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                @forelse($authObjects as $authObject)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $authObject->code }}</td>
                        <td class="px-6 py-4">{{ $authObject->description }}</td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $authObject->fields->count() }} field(s)</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.authorization.auth-objects.edit', $authObject) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">Edit</a>
                            <form action="{{ route('admin.authorization.auth-objects.destroy', $authObject) }}" method="POST" class="inline-block ml-4">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-[#706f6c] dark:text-[#A1A09A]">No authorization objects found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $authObjects->links() }}
</div>
@endsection

