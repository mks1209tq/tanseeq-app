@extends('ui::layouts.app')

@section('title', 'Navigation')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold">Navigation</h1>
        </div>

        @if(empty($groupedItems))
            <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
                <p class="text-[#706f6c] dark:text-[#A1A09A]">No navigation items available.</p>
            </div>
        @else
            @foreach($groupedItems as $group => $items)
                <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg p-6 shadow-sm">
                    <h2 class="text-xl font-semibold mb-4 text-[#1b1b18] dark:text-[#EDEDEC]">
                        {{ ucfirst($group) }}
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($items as $item)
                            <div class="group relative p-4 bg-[#FDFDFC] dark:bg-[#0a0a0a] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg hover:bg-[#f5f5f3] dark:hover:bg-[#1a1a18] transition-colors">
                                <a href="{{ route($item['route']) }}" 
                                   class="block">
                                    <div class="flex items-center gap-3">
                                        @if($item['icon'])
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded">
                                                <i class="icon-{{ $item['icon'] }}"></i>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ $item['label'] }}</h3>
                                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] truncate" 
                                               data-copyable="{{ route($item['route']) }}"
                                               title="{{ $item['path'] ?? $item['uri'] }} (Click to copy)">
                                                {{ $item['path'] ?? $item['uri'] }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
