@props([
    'for' => null,
    'required' => false,
])

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'block text-label text-foreground']) }}>
    {{ $slot }}

    @if ($required)
        <span class="text-danger" aria-hidden="true">*</span>
        <span class="sr-only">{{ __('common.form.required') }}</span>
    @endif
</label>
