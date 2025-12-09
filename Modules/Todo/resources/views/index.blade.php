@extends('ui::layouts.app')

@section('title', 'Todos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('todo.index') }}" class="text-lg font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-1 hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Todo Manager</a>
            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">My Todos</h1>
        </div>
        <button onclick="document.getElementById('create-todo-form').classList.toggle('hidden')" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
            + New Todo
        </button>
    </div>

    <!-- Create Todo Form -->
    <div id="create-todo-form" class="hidden bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
        <h2 class="text-xl font-semibold mb-4">Create New Todo</h2>
        <form action="{{ route('todo.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="title" class="block text-sm font-medium mb-1">Title *</label>
                <input type="text" name="title" id="title" required value="{{ old('title') }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
            </div>
            <div>
                <label for="description" class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="priority" class="block text-sm font-medium mb-1">Priority</label>
                    <select name="priority" id="priority" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label for="due_date" class="block text-sm font-medium mb-1">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" min="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    Create Todo
                </button>
                <button type="button" onclick="document.getElementById('create-todo-form').classList.add('hidden')" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-4 shadow-sm">
        <form method="GET" action="{{ route('todo.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search todos..." class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" id="status" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div>
                    <label for="priority" class="block text-sm font-medium mb-1">Priority</label>
                    <select name="priority" id="priority" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                        <option value="">All</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div>
                    <label for="sort_by" class="block text-sm font-medium mb-1">Sort By</label>
                    <select name="sort_by" id="sort_by" class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                        <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Created Date</option>
                        <option value="due_date" {{ request('sort_by') === 'due_date' ? 'selected' : '' }}>Due Date</option>
                        <option value="priority" {{ request('sort_by') === 'priority' ? 'selected' : '' }}>Priority</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    Apply Filters
                </button>
                <a href="{{ route('todo.index') }}" class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors inline-block">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Todos List -->
    @if($todos->count() > 0)
        <div class="grid gap-4">
            @foreach($todos as $todo)
                @include('todo::partials.todo-card', ['todo' => $todo])
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $todos->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-12 text-center">
            <p class="text-[#706f6c] dark:text-[#A1A09A] text-lg">No todos found. Create your first todo to get started!</p>
        </div>
    @endif
</div>
@endsection
