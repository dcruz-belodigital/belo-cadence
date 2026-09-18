@props([
    'name',
    'id' => null,
    'errorKey' => null,
    'rows' => 4,
])

@php
    // A grouped field submits as client_attributes[7] but its message arrives under
    // client_attributes.7, so the two can differ — the same split `x-form.checkbox`
    // already carries.
    $errorKey ??= $name;
@endphp

<textarea name="{{ $name }}"
          id="{{ $id ?? $name }}"
          rows="{{ $rows }}"
          @error($errorKey)
              aria-invalid="true"
              aria-describedby="{{ $id ?? $name }}-error"
          @enderror
          {{ $attributes->merge(['class' => 'form-control']) }}>{{ $slot }}</textarea>
