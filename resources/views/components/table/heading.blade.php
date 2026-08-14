@props(['align' => 'left'])

<th scope="col" {{ $attributes->merge([
    'class' => 'px-4 py-2.5 text-overline uppercase text-foreground-muted '.($align === 'right' ? 'text-right' : 'text-left'),
]) }}>
    {{ $slot }}
</th>
