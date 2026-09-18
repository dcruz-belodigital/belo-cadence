@php
    $showLink ??= true;

    /*
    | The permissions are read once rather than through `@can` at each entry, because a
    | menu with nothing in it should not draw a trigger at all — a reader who may only
    | look at schedules gets no button rather than an empty panel.
    */
    $viewer = auth()->user();

    $mayView = $showLink && $viewer->can('view', $schedule);
    $maySend = $viewer->can('send', $schedule);
    $mayUpdate = $viewer->can('update', $schedule);
@endphp

@if ($mayView || $maySend || $mayUpdate)
    <x-table.actions>
        @if ($mayView)
            <x-dropdown.item :href="route('cadence.schedules.show', $schedule)" icon="eye">
                {{ __('common.actions.view') }}
            </x-dropdown.item>
        @endif

        @if ($mayUpdate)
            <x-dropdown.item :href="route('cadence.schedules.edit', $schedule)" icon="pencil">
                {{ __('common.actions.edit') }}
            </x-dropdown.item>
        @endif

        {{-- Below the line, the entries that send email or change what the schedule does next. --}}
        @if (($mayView || $mayUpdate) && ($maySend || $mayUpdate))
            <x-dropdown.divider />
        @endif

        @if ($maySend)
            <x-confirm-form :action="route('cadence.schedules.send', $schedule)"
                            method="POST"
                            trigger-as="menu-item"
                            :title="__('cadence.send.title')"
                            :message="__('cadence.send.message')"
                            :confirm="__('cadence.send.confirm')"
                            variant="primary"
                            icon="send">
                <x-slot:trigger>{{ __('cadence.actions.send') }}</x-slot:trigger>
            </x-confirm-form>
        @endif

        @if ($mayUpdate)
            @if ($schedule->is_enabled)
                <x-confirm-form :action="route('cadence.schedules.disable', $schedule)"
                                method="POST"
                                trigger-as="menu-item"
                                :title="__('cadence.disable.title')"
                                :message="__('cadence.disable.message')"
                                :confirm="__('cadence.disable.confirm')"
                                variant="primary"
                                icon="x-circle">
                    <x-slot:trigger>{{ __('cadence.actions.disable') }}</x-slot:trigger>
                </x-confirm-form>
            @else
                <x-confirm-form :action="route('cadence.schedules.enable', $schedule)"
                                method="POST"
                                trigger-as="menu-item"
                                :title="__('cadence.enable.title')"
                                :message="__('cadence.enable.message')"
                                :confirm="__('cadence.enable.confirm')"
                                variant="primary"
                                icon="check-circle">
                    <x-slot:trigger>{{ __('cadence.actions.enable') }}</x-slot:trigger>
                </x-confirm-form>
            @endif
        @endif
    </x-table.actions>
@endif
