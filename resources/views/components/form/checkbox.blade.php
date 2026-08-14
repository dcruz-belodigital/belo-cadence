@props([
    'name',
    'id' => null,
    'label' => null,
    'hint' => null,
    'checked' => false,
    'value' => '1',
    'errorKey' => null,
])

@php
    // A grouped checkbox submits as schedules[0][is_enabled] but its message arrives
    // under schedules.0.is_enabled, so the two can differ.
    $errorKey ??= $name;
@endphp

<div class="flex items-start gap-2.5">
    {{-- The hidden field means an unchecked box still submits a value. --}}
    <input type="hidden" name="{{ $name }}" value="0">

    <input type="checkbox"
           name="{{ $name }}"
           id="{{ $id ?? $name }}"
           value="{{ $value }}"
           @checked($checked)
           @error($errorKey)
               aria-invalid="true"
               aria-describedby="{{ $errorKey }}-error"
           @enderror
           {{ $attributes->merge([
               'class' => 'focus-ring mt-0.5 size-4 shrink-0 rounded border-border-strong bg-surface text-primary accent-primary',
           ]) }}>

    @if ($label)
        <div class="space-y-0.5">
            <label for="{{ $id ?? $name }}" class="block text-label text-foreground">{{ $label }}</label>

            @if ($hint)
                <p class="text-meta text-foreground-subtle">{{ $hint }}</p>
            @endif

            <x-form.error :name="$errorKey" />
        </div>
    @else
        <x-form.error :name="$errorKey" />
    @endif
</div>
