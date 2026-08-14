@props(['title'])

{{-- The label is set against a rule that runs out to the edge, the way a printed index divides its sections. --}}
<div class="space-y-1.5">
    <div class="flex items-center gap-2.5 px-2.5">
        <p class="text-overline uppercase text-foreground-subtle">{{ $title }}</p>
        <span class="h-px flex-1 bg-border" aria-hidden="true"></span>
    </div>

    <ul class="space-y-0.5">
        {{ $slot }}
    </ul>
</div>
