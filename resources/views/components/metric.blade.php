@props([
    'label',
    'value',
    'icon' => null,
    'variant' => 'neutral',
    'href' => null,
    'description' => null,
])

@php
    /*
    | The variant thickens the tile's leading border rather than floating a bar inside
    | it, so a row of metrics reads as a scale of severity at a glance and the accent
    | turns the corner with the card instead of sitting apart from it. This is the same
    | treatment `x-alert` uses; edge accents are drawn one way in this application.
    |
    | Each class name is written out in full because Tailwind reads these files as
    | plain text to decide what to compile.
    */
    $edges = [
        'neutral' => 'border-l-border-strong',
        'success' => 'border-l-success',
        'warning' => 'border-l-warning',
        'danger' => 'border-l-danger',
        'info' => 'border-l-info',
    ];

    $glyphs = [
        'neutral' => 'text-foreground-subtle',
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        'info' => 'text-info',
    ];

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge([
        'class' => 'block rounded-card border border-l-[3px] border-border bg-surface py-4 pl-5 pr-4 transition '
            .($edges[$variant] ?? $edges['neutral']).' '
            .($href ? 'focus-ring hover:bg-surface-hover' : ''),
    ]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="min-w-0 text-overline uppercase text-foreground-subtle">{{ $label }}</p>

        @if ($icon)
            <x-icon :name="$icon" size="size-4" class="{{ $glyphs[$variant] ?? $glyphs['neutral'] }}" />
        @endif
    </div>

    <p class="mt-2.5 font-mono text-metric numeric text-foreground">{{ $value }}</p>

    @if ($description)
        <p class="mt-1 text-meta text-foreground-muted">{{ $description }}</p>
    @endif
</{{ $tag }}>
