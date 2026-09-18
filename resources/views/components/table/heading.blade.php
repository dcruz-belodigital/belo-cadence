@props(['align' => 'left'])

{{--
    A column keeps its name on one line, here and in `x-table.sort-heading`. A heading
    that wrapped made the head of the table taller than the rows under it and split the
    sort arrow from the words it belongs to; a table that needs the extra width already
    scrolls sideways inside its card.
--}}
<th scope="col" {{ $attributes->merge([
    'class' => 'px-4 py-2.5 text-overline whitespace-nowrap uppercase text-foreground-muted '.($align === 'right' ? 'text-right' : 'text-left'),
]) }}>
    {{ $slot }}
</th>
