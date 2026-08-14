@if ($schedule->isCompleted())
    <x-badge variant="neutral" icon="check-circle" :title="__('cadence.state_hints.completed')">
        {{ __('cadence.states.completed') }}
    </x-badge>
@elseif (! $schedule->is_enabled)
    <x-badge variant="neutral">{{ __('cadence.states.disabled') }}</x-badge>
@elseif ($schedule->next_send_at === null)
    <x-badge variant="warning" icon="warning" :title="__('cadence.state_hints.no_next_occurrence')">
        {{ __('cadence.states.no_next_occurrence') }}
    </x-badge>
@else
    <x-badge variant="success">{{ __('cadence.states.enabled') }}</x-badge>
@endif
