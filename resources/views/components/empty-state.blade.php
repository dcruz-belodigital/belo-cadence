@props([
    'title',
    'description' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 px-6 py-12 text-center']) }}>
    <span class="flex size-11 items-center justify-center rounded-card border border-border bg-surface-sunken text-foreground-subtle">
        <x-icon :name="$icon" size="size-5" />
    </span>

    <div class="space-y-1">
        <p class="text-section-title text-foreground">{{ $title }}</p>

        @if ($description)
            <p class="mx-auto max-w-md text-meta text-foreground-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="mt-1 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
