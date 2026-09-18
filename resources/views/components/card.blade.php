@props([
    'title' => null,
    'description' => null,
    'flush' => false,
])

{{-- `overflow-hidden` clips the header tint, a flush row's fill and the footer to the radius; without it they square off the corners. --}}
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-card border border-border bg-surface shadow-card']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-surface-sunken/50 px-4 py-3 sm:px-5">
            {{-- The tick is centred on the title's own line, so it does not drift down a heading that carries a description. --}}
            <div class="flex min-w-0 items-start gap-2.5">
                @if ($title)
                    <span class="tick mt-[0.3125rem]" aria-hidden="true"></span>
                @endif

                <div class="min-w-0 space-y-0.5">
                    @if ($title)
                        <h2 class="truncate text-section-title">{{ $title }}</h2>
                    @endif

                    @if ($description)
                        <p class="text-meta text-foreground-muted">{{ $description }}</p>
                    @endif
                </div>
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $flush ? '' : 'px-4 py-4 sm:px-5 sm:py-5' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        {{ $footer }}
    @endisset
</section>
