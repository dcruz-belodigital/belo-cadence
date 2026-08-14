@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
    'icon' => null,
    'iconAfter' => null,
])

@php
    /*
    | The btn-* utilities live in the design system, so every button, dropdown trigger
    | and link that looks like a button is styled from one place.
    |
    | Each class name is written out in full on purpose. Tailwind reads these files as
    | plain text to decide what to compile, so a name assembled at runtime, such as
    | 'btn-'.$variant, would never reach the stylesheet.
    */
    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'ghost' => 'btn-ghost',
        default => 'btn-primary',
    };

    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'icon' => 'btn-icon',
        default => 'btn-md',
    };

    $classes = "btn {$variantClass} {$sizeClass}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-icon :name="$icon" size="size-4" />
        @endif

        {{ $slot }}

        @if ($iconAfter)
            <x-icon :name="$iconAfter" size="size-4" />
        @endif
    </a>
@else
    {{-- Submitting disables the button, so a slow request cannot be sent twice. --}}
    <button type="{{ $type }}"
            @if ($type === 'submit')
                x-data="{ submitting: false }"
                x-init="$el.form?.addEventListener('submit', () => { submitting = true })"
                x-bind:disabled="submitting"
                x-bind:aria-busy="submitting"
            @endif
            {{ $attributes->merge(['class' => $classes]) }}>
        @if ($type === 'submit')
            <span x-show="submitting"
                  x-cloak
                  class="size-4 animate-spin rounded-pill border-2 border-current border-t-transparent"
                  aria-hidden="true"></span>
        @endif

        @if ($icon)
            <x-icon :name="$icon" size="size-4" />
        @endif

        {{ $slot }}

        @if ($iconAfter)
            <x-icon :name="$iconAfter" size="size-4" />
        @endif
    </button>
@endif
