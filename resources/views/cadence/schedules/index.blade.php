@php
    use App\Models\NotificationSchedule;
@endphp

<x-app-layout :heading="__('cadence.title')">
    <x-page-header :description="__('cadence.description')">
        <x-slot:actions>
            @can('export', NotificationSchedule::class)
                <x-export-menu :action="route('cadence.schedules.export')" />
            @endcan

            @can('create', NotificationSchedule::class)
                <x-button :href="route('cadence.schedules.create')" icon="plus">
                    {{ __('cadence.actions.create_list') }}
                </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @include('cadence.partials.filters', [
        'action' => route('cadence.schedules.index'),
        'filters' => $filters,
    ])

    <x-table :label="__('cadence.title')">
        <x-slot:head>
            <x-table.sort-heading column="client">{{ __('cadence.columns.name') }}</x-table.sort-heading>
            <x-table.heading>{{ __('cadence.columns.target') }}</x-table.heading>
            <x-table.sort-heading column="template">{{ __('cadence.columns.template') }}</x-table.sort-heading>
            <x-table.sort-heading column="frequency">{{ __('cadence.columns.frequency') }}</x-table.sort-heading>
            <x-table.sort-heading column="next_send_at">{{ __('cadence.columns.next_send_at') }}</x-table.sort-heading>
            <x-table.sort-heading column="last_sent_at">{{ __('cadence.columns.last_sent_at') }}</x-table.sort-heading>
            <x-table.heading>{{ __('cadence.columns.state') }}</x-table.heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($schedules as $schedule)
            <x-table.row>
                <x-table.cell>
                    @if ($schedule->client !== null)
                        <a href="{{ route('clients.show', $schedule->client) }}" class="focus-ring rounded-control font-medium transition hover:text-primary">
                            {{ $schedule->client->name }}
                        </a>
                    @else
                        <a href="{{ route('cadence.schedules.show', $schedule) }}" class="focus-ring rounded-control font-medium transition hover:text-primary">
                            {{ $schedule->displayName() }}
                        </a>
                    @endif
                </x-table.cell>

                <x-table.cell>
                    <x-badge :variant="$schedule->target->badgeVariant()">{{ $schedule->target->label() }}</x-badge>
                </x-table.cell>

                <x-table.cell>{{ $schedule->template->label() }}</x-table.cell>
                <x-table.cell muted>{{ $schedule->frequency->label() }}</x-table.cell>
                <x-table.cell><x-datetime :value="$schedule->next_send_at" /></x-table.cell>
                <x-table.cell muted><x-datetime :value="$schedule->last_sent_at" /></x-table.cell>

                <x-table.cell>
                    @include('cadence.partials.schedule-state', ['schedule' => $schedule])
                </x-table.cell>

                <x-table.cell align="right">
                    @include('cadence.partials.schedule-actions', ['schedule' => $schedule])
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="8">
                    <x-empty-state :title="$filters->isActive() ? __('cadence.empty.filtered_title') : __('cadence.empty.title')"
                                   :description="$filters->isActive() ? __('cadence.empty.filtered_description') : __('cadence.empty.description')"
                                   icon="clock" />
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$schedules" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
