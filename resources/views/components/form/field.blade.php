@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'required' => false,
    'errorKey' => null,
])

@php
    $errorKey = $errorKey ?? $name;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <x-form.label :for="$name" :required="$required">{{ $label }}</x-form.label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-meta text-foreground-subtle" @if ($name) id="{{ $name }}-hint" @endif>{{ $hint }}</p>
    @endif

    @if ($errorKey)
        <x-form.error :name="$errorKey" />
    @endif
</div>
