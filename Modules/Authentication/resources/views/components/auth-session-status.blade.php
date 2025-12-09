@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200']) }}>
        {{ $status }}
    </div>
@endif

