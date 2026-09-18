@props(['name', 'id' => null])

{{--
    The one and only way a validation message is shown. Every form uses this, so an error
    always reads the same wherever it appears.

    `name` accepts a single key, or a list of keys for the messages that belong to no single
    field — a whole array of rows, or fields a script renders client side. Wildcards such as
    `entries.*` are understood. Nothing at all is rendered when there is nothing wrong, so
    any spacing passed in cannot leave a gap behind.

    The message's id follows the input's id rather than its name, because two inputs can
    submit the same name — one per branch of a form — and only one id may exist per page.
--}}
@php
    $keys = is_array($name) ? $name : [$name];

    $anchorId = $id ?? (is_string($name) ? $name : null);

    $messages = collect($keys)
        ->flatMap(fn (string $key): array => Illuminate\Support\Arr::flatten($errors->get($key)))
        ->unique()
        ->values();
@endphp

@if ($messages->isNotEmpty())
    <div {{ $attributes->class('space-y-1') }}>
        @foreach ($messages as $index => $message)
            <p @if ($index === 0 && $anchorId !== null) id="{{ $anchorId }}-error" @endif
               class="text-meta text-danger"
               role="alert">{{ $message }}</p>
        @endforeach
    </div>
@endif
