@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    /* The leading edge is the solid accent; the rest of the panel stays quiet behind it. */
    $variants = [
        'success' => ['class' => 'border-success/25 border-l-success bg-success-soft text-success-soft-foreground', 'icon' => 'check-circle'],
        'warning' => ['class' => 'border-warning/25 border-l-warning bg-warning-soft text-warning-soft-foreground', 'icon' => 'warning'],
        'danger' => ['class' => 'border-danger/25 border-l-danger bg-danger-soft text-danger-soft-foreground', 'icon' => 'x-circle'],
        'info' => ['class' => 'border-info/25 border-l-info bg-info-soft text-info-soft-foreground', 'icon' => 'info'],
    ];

    $resolved = $variants[$variant] ?? $variants['info'];
@endphp

<div @if ($dismissible) x-data="{ shown: true }" x-show="shown" @endif
     role="{{ in_array($variant, ['danger', 'warning'], true) ? 'alert' : 'status' }}"
     {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-card border border-l-[3px] px-4 py-3 '.$resolved['class']]) }}>
    <x-icon :name="$resolved['icon']" size="size-5" class="mt-px" />

    <div class="min-w-0 flex-1 space-y-1">
        @if ($title)
            <p class="text-label">{{ $title }}</p>
        @endif

        <div class="text-body">{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button type="button" x-on:click="shown = false" class="focus-ring -m-1 rounded-control p-1 transition hover:opacity-70">
            <x-icon name="x-mark" size="size-4" />
            <span class="sr-only">{{ __('common.actions.dismiss') }}</span>
        </button>
    @endif
</div>
