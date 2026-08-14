@props([
    'description' => null,
])

{{--
    The page's name and its back link live in the top bar. What is left here is the
    sentence explaining the page and the actions that belong to it.
--}}
@if ($description || isset($actions))
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        @if ($description)
            <p class="max-w-2xl text-meta text-foreground-muted">{{ $description }}</p>
        @else
            <span></span>
        @endif

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
@endif
