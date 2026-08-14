@props([
    'href' => null,
    'icon' => null,
    'variant' => 'default',
    'type' => 'button',
])

@php
    /*
    | A destructive entry is red all the way through — label, glyph and the ground it
    | sits on — rather than red text beside a grey glyph, which read as an accident.
    | Letting the icon inherit `currentColor` is what keeps the two in step.
    */
    $isDanger = $variant === 'danger';

    $classes = 'focus-ring-inset flex w-full items-center gap-2.5 px-3 py-2.5 text-left text-body transition '
        .($isDanger
            ? 'text-danger hover:bg-danger-soft hover:text-danger-soft-foreground'
            : 'text-foreground hover:bg-surface-muted');

    $iconClass = $isDanger ? '' : 'text-foreground-subtle';
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-icon :name="$icon" size="size-4" :class="$iconClass" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-icon :name="$icon" size="size-4" :class="$iconClass" />
        @endif
        {{ $slot }}
    </button>
@endif
