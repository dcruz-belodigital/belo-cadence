@props([
    'href',
    'icon' => null,
    'active' => false,
])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   class="nav-link {{ $active ? 'nav-link-active' : '' }}">
    @if ($icon)
        <x-icon :name="$icon" size="size-4.5" />
    @endif

    <span class="truncate">{{ $slot }}</span>
</a>
