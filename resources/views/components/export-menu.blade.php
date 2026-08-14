@props([
    'action',
    'params' => [],
])

@php
    // The export keeps the reader's current search, filters and sorting, minus the page.
    // Extra parameters belong here rather than in the action URL, so the query string is
    // always built in one piece.
    $query = request()->query();
    unset($query['page']);
    $query = array_merge($query, $params);
@endphp

<x-dropdown align="right" width="w-72" trigger-class="btn btn-secondary btn-md" :label="__('common.actions.export')">
    <x-slot:trigger>
        <x-icon name="download" size="size-4" />
        {{ __('common.actions.export') }}
    </x-slot:trigger>

    <x-dropdown.item :href="$action.'?'.http_build_query([...$query, 'mode' => 'table'])" icon="document">
        <span class="flex flex-col">
            <span>{{ __('exports.modes.table') }}</span>
            <span class="text-meta text-foreground-subtle">{{ __('exports.hints.table') }}</span>
        </span>
    </x-dropdown.item>

    <x-dropdown.item :href="$action.'?'.http_build_query([...$query, 'mode' => 'raw'])" icon="download">
        <span class="flex flex-col">
            <span>{{ __('exports.modes.raw') }}</span>
            <span class="text-meta text-foreground-subtle">{{ __('exports.hints.raw') }}</span>
        </span>
    </x-dropdown.item>
</x-dropdown>
