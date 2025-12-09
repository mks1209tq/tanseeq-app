@extends('ui::layouts.app')

@section('title', 'View Company')

@section('content')
<div class="max-w-2xl">
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('company.index') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Company Manager</a>
            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">Company Details</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('company.edit', $company) }}" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                Edit
            </a>
            <a href="{{ route('company.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors inline-block">
                Back
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm space-y-4">
        <div>
            <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Company Name</label>
            <p class="text-lg font-medium">{{ $company->name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Created At</label>
            <p class="text-[#706f6c] dark:text-[#A1A09A]">{{ $company->created_at->format('F d, Y \a\t g:i A') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] mb-1">Updated At</label>
            <p class="text-[#706f6c] dark:text-[#A1A09A]">{{ $company->updated_at->format('F d, Y \a\t g:i A') }}</p>
        </div>
    </div>
</div>
@endsection

