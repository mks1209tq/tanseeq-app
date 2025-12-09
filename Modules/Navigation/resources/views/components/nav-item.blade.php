<a href="{{ route($route) }}" class="{{ $class }}">
    @if($icon)
        <i class="icon-{{ $icon }}"></i>
    @endif
    {{ $label }}
</a>

