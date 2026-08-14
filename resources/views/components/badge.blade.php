@props([
    'variant' => 'neutral',
    'icon' => null,
])

@php
    $variant = $variant instanceof \App\Enums\BadgeVariant ? $variant->value : $variant;

    $variants = [
        'neutral' => 'border-border-strong bg-neutral-soft text-neutral-soft-foreground',
        'success' => 'border-success/30 bg-success-soft text-success-soft-foreground',
        'warning' => 'border-warning/30 bg-warning-soft text-warning-soft-foreground',
        'danger' => 'border-danger/30 bg-danger-soft text-danger-soft-foreground',
        'info' => 'border-info/30 bg-info-soft text-info-soft-foreground',
    ];
@endphp

{{--
    Status is never communicated by colour alone: the label is always present and says
    what the colour means, so the chip needs nothing in front of it. An icon is still
    shown when one is passed, where it adds meaning rather than just marking the chip.
--}}
<span {{ $attributes->merge([
    'class' => 'badge '.($variants[$variant] ?? $variants['neutral']),
]) }}>
    @if ($icon)
        <x-icon :name="$icon" size="size-3.5" />
    @endif

    {{ $slot }}
</span>
