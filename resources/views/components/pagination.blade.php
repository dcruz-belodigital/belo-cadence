@props(['paginator'])

@if ($paginator->total() > 0)
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-3"
         role="navigation"
         aria-label="{{ __('common.pagination.label') }}">
        <p class="text-meta text-foreground-muted">
            {{ __('common.pagination.summary', [
                'first' => $paginator->firstItem(),
                'last' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </p>

        @if ($paginator->hasPages())
            @php
                $window = 2;
                $start = max(1, $paginator->currentPage() - $window);
                $end = min($paginator->lastPage(), $paginator->currentPage() + $window);
            @endphp

            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex size-control-sm items-center justify-center rounded-control text-foreground-subtle" aria-hidden="true">
                        <x-icon name="chevron-left" size="size-4" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}"
                       rel="prev"
                       aria-label="{{ __('common.pagination.previous') }}"
                       class="focus-ring inline-flex size-control-sm items-center justify-center rounded-control text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                        <x-icon name="chevron-left" size="size-4" />
                    </a>
                @endif

                @foreach (range($start, $end) as $page)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page"
                              class="inline-flex size-control-sm items-center justify-center rounded-control bg-primary px-2 text-meta font-medium text-primary-foreground">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($page) }}"
                           class="focus-ring inline-flex size-control-sm items-center justify-center rounded-control px-2 text-meta text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}"
                       rel="next"
                       aria-label="{{ __('common.pagination.next') }}"
                       class="focus-ring inline-flex size-control-sm items-center justify-center rounded-control text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                        <x-icon name="chevron-right" size="size-4" />
                    </a>
                @else
                    <span class="inline-flex size-control-sm items-center justify-center rounded-control text-foreground-subtle" aria-hidden="true">
                        <x-icon name="chevron-right" size="size-4" />
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif
