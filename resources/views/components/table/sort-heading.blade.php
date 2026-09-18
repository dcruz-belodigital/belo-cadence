@props([
    'column',
    'align' => 'left',
])

@php
    $active = request()->string('sort')->toString() === $column;
    $direction = request()->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
    $nextDirection = $active && $direction === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => null]);
@endphp

<th scope="col"
    aria-sort="{{ $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    class="px-4 py-2.5 text-overline whitespace-nowrap uppercase text-foreground-muted {{ $align === 'right' ? 'text-right' : 'text-left' }}">
    <a href="{{ $url }}"
       class="focus-ring inline-flex items-center gap-1 rounded-control transition hover:text-foreground {{ $active ? 'text-foreground' : '' }}">
        {{ $slot }}

        @if ($active)
            <x-icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'" size="size-3.5" />
        @else
            <x-icon name="sort" size="size-3.5" class="opacity-40" />
        @endif
    </a>
</th>
