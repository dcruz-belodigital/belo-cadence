@props([
    'preview' => 'template',
    'disabled' => null,
])

{{--
    Wraps a template control and fixes the preview action to its trailing edge, so every
    place that chooses a template offers it the same way.

    `preview` is the Alpine expression naming the template to show — usually the same
    thing the control is bound to. `disabled` is an Alpine expression for the branches of
    a form that are not currently in use; the control inside keeps its own, because
    whether it submits is its business rather than the action's.
--}}
<div {{ $attributes->merge(['class' => 'field-with-action']) }}>
    {{ $slot }}

    <button type="button"
            class="field-action"
            @if ($disabled) x-bind:disabled="{{ $disabled }}" @endif
            x-on:click="$dispatch('preview-template', { template: {{ $preview }} })">
        <x-icon name="eye" size="size-4" />

        <span class="field-action-label" aria-hidden="true">{{ __('cadence.templates.preview') }}</span>
        <span class="sr-only">{{ __('cadence.templates.preview') }}</span>
    </button>
</div>
