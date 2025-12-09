@php
    $navigationService = app(\Modules\Navigation\Services\NavigationService::class);
    $items = $navigationService->getAuthorizedItems($group ?? null);
@endphp

@foreach($items as $item)
    <x-navigation::nav-item
        :route="$item['route']"
        :label="$item['label']"
        :icon="$item['icon'] ?? null"
        :class="$item['class'] ?? 'text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]'"
    />
@endforeach

