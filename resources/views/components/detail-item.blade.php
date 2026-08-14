@props([
    'label',
    'wide' => false,
])

<div class="{{ $wide ? 'sm:col-span-2' : '' }} min-w-0 space-y-1">
    <dt class="text-overline uppercase text-foreground-muted">{{ $label }}</dt>
    <dd class="text-body break-words">{{ $slot }}</dd>
</div>
