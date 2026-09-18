@props([
    'name',
    'id' => null,
    'errorKey' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

@php
    // A grouped field submits as client_attributes[7] but its message arrives under
    // client_attributes.7, so the two can differ — the same split `x-form.checkbox`
    // already carries.
    $errorKey ??= $name;
@endphp

<select name="{{ $name }}"
        id="{{ $id ?? $name }}"
        @error($errorKey)
            aria-invalid="true"
            aria-describedby="{{ $id ?? $name }}-error"
        @enderror
        {{ $attributes->merge(['class' => 'form-control pr-2']) }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $value === (string) $selected)>{{ $label }}</option>
    @endforeach
</select>
