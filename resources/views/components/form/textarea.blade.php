@props([
    'name',
    'id' => null,
    'rows' => 4,
])

<textarea name="{{ $name }}"
          id="{{ $id ?? $name }}"
          rows="{{ $rows }}"
          @error($name)
              aria-invalid="true"
              aria-describedby="{{ $id ?? $name }}-error"
          @enderror
          {{ $attributes->merge(['class' => 'form-control']) }}>{{ $slot }}</textarea>
