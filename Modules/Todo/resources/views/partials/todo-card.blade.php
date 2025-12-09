@php
    $priorityColors = [
        'high' => 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200 border-red-200 dark:border-red-800',
        'medium' => 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 border-yellow-200 dark:border-yellow-800',
        'low' => 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 border-green-200 dark:border-green-800',
    ];
    $priorityColor = $priorityColors[$todo->priority] ?? $priorityColors['medium'];
    
    $isOverdue = $todo->due_date && $todo->due_date->isPast() && !$todo->completed;
@endphp

<div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow {{ $todo->completed ? 'opacity-75' : '' }}">
    <div class="flex items-start gap-4">
        <form action="{{ route('todo.toggle', $todo) }}" method="POST" class="mt-1">
            @csrf
            <input type="checkbox" 
                   {{ $todo->completed ? 'checked' : '' }} 
                   onchange="this.form.submit()"
                   class="w-5 h-5 rounded border-[#e3e3e0] dark:border-[#3E3E3A] text-[#1b1b18] dark:text-[#EDEDEC] focus:ring-2 focus:ring-black dark:focus:ring-white">
        </form>
        
        <div class="flex-1">
            <div class="flex items-start justify-between gap-4 mb-2">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] {{ $todo->completed ? 'line-through' : '' }}">
                        {{ $todo->title }}
                    </h3>
                    @if($todo->description)
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1 {{ $todo->completed ? 'line-through' : '' }}">
                            {{ $todo->description }}
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 text-xs font-medium rounded border {{ $priorityColor }}">
                        {{ ucfirst($todo->priority) }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center gap-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    @if($todo->due_date)
                        <span class="{{ $isOverdue ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                            Due: {{ $todo->due_date->format('M d, Y') }}
                            @if($isOverdue)
                                <span class="ml-1">(Overdue)</span>
                            @endif
                        </span>
                    @endif
                    <span>Created: {{ $todo->created_at->format('M d, Y') }}</span>
                </div>
                
                <div class="flex items-center gap-2">
                    <a href="{{ route('todo.edit', $todo) }}" class="px-3 py-1 text-sm border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm hover:bg-gray-50 dark:hover:bg-[#3E3E3A] transition-colors">
                        Edit
                    </a>
                    <form action="{{ route('todo.destroy', $todo) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this todo?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1 text-sm bg-red-600 dark:bg-red-800 text-white rounded-sm hover:bg-red-700 dark:hover:bg-red-900 transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

