@props([
    'name',
    'type' => 'text',
    'id' => null,
])

@php
    /*
    | A file input is not a box you type in, so it gets its own utility. Written out in
    | full rather than assembled, so Tailwind can see both class names.
    */
    $class = $type === 'file' ? 'form-file' : 'form-control';
@endphp

@if ($type === 'password')
    {{--
        A password can be revealed, because the alternative is retyping a long one from
        scratch after a single mistyped character. The field is a real password input
        until the reader asks otherwise, so browsers still offer to save and fill it.
    --}}
    <div class="relative" x-data="{ shown: false }">
        <input x-bind:type="shown ? 'text' : 'password'"
               type="password"
               name="{{ $name }}"
               id="{{ $id ?? $name }}"
               @error($name)
                   aria-invalid="true"
                   aria-describedby="{{ $id ?? $name }}-error"
               @enderror
               {{ $attributes->merge(['class' => 'form-control pr-11']) }}>

        <button type="button"
                x-on:click="shown = ! shown"
                x-bind:aria-label="shown ? @js(__('common.actions.hide_password')) : @js(__('common.actions.show_password'))"
                x-bind:aria-pressed="shown ? 'true' : 'false'"
                class="focus-ring absolute inset-y-0 right-0 flex items-center rounded-control px-3 text-foreground-subtle transition hover:text-foreground">
            <x-icon name="eye" size="size-4" x-show="! shown" />
            <x-icon name="eye-off" size="size-4" x-show="shown" x-cloak />
        </button>
    </div>
@else
    <input type="{{ $type }}"
           name="{{ $name }}"
           id="{{ $id ?? $name }}"
           @error($name)
               aria-invalid="true"
               aria-describedby="{{ $id ?? $name }}-error"
           @enderror
           {{ $attributes->merge(['class' => $class]) }}>
@endif
