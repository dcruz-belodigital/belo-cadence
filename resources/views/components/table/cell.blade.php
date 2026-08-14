@props([
    'align' => 'left',
    'muted' => false,
])

<td {{ $attributes->merge([
    'class' => 'px-4 py-3 align-middle '
        .($align === 'right' ? 'text-right ' : '')
        .($muted ? 'text-foreground-muted' : ''),
]) }}>
    {{ $slot }}
</td>
