@extends('ui::layouts.app')

@section('title', 'Companies')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('company.index') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Company Manager</a>
            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Companies</h1>
        </div>
        <a href="{{ route('company.create') }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
            + New Company
        </a>
    </div>

    @if($companies->count() > 0)
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                <thead class="bg-gray-50 dark:bg-[#1C1C1A]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-[#706f6c] dark:text-[#A1A09A] uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#e3e3e0] dark:divide-[#3E3E3A]">
                    @foreach($companies as $company)
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#1C1C1A]">
                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $company->name }}</td>
                            <td class="px-6 py-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $company->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('company.show', $company) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-4">View</a>
                                <a href="{{ route('company.edit', $company) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-4">Edit</a>
                                <form action="{{ route('company.destroy', $company) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this company?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $companies->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-12 text-center">
            <p class="text-[#706f6c] dark:text-[#A1A09A] text-lg">No companies found. Create your first company to get started!</p>
        </div>
    @endif
</div>
@endsection
