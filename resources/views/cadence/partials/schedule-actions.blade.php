@php
    $showLink ??= true;
@endphp

<div class="flex items-center justify-end gap-1">
    @if ($showLink)
        @can('view', $schedule)
            <x-button :href="route('cadence.schedules.show', $schedule)" variant="ghost" size="sm" icon="eye">
                {{ __('common.actions.view') }}
            </x-button>
        @endcan
    @endif

    @can('update', $schedule)
        @if ($schedule->is_enabled)
            <form method="POST" action="{{ route('cadence.schedules.disable', $schedule) }}">
                @csrf
                <x-button type="submit" variant="ghost" size="sm">{{ __('cadence.actions.disable') }}</x-button>
            </form>
        @else
            <form method="POST" action="{{ route('cadence.schedules.enable', $schedule) }}">
                @csrf
                <x-button type="submit" variant="ghost" size="sm">{{ __('cadence.actions.enable') }}</x-button>
            </form>
        @endif

        <x-button :href="route('cadence.schedules.edit', $schedule)" variant="ghost" size="sm" icon="pencil">
            {{ __('common.actions.edit') }}
        </x-button>
    @endcan
</div>
