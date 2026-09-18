@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'required' => false,
    'errorKey' => null,
    'inputId' => null,
])

@php
    $errorKey = $errorKey ?? $name;
    $inputId = $inputId ?? (is_string($name) ? $name : null);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <x-form.label :for="$inputId" :required="$required">{{ $label }}</x-form.label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-meta text-foreground-subtle" @if ($inputId) id="{{ $inputId }}-hint" @endif>{{ $hint }}</p>
    @endif

    @if ($errorKey)
        <x-form.error :name="$errorKey" :id="$inputId" />
    @endif
</div>
