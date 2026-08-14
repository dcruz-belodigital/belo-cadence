@php
    use App\Models\ClientNotificationSchedule;
@endphp

<x-app-layout :heading="__('cadence.upcoming.title')">
    <x-page-header :description="__('cadence.upcoming.description')">
        <x-slot:actions>
            @can('export', ClientNotificationSchedule::class)
                <x-export-menu :action="route('cadence.schedules.export')" :params="['upcoming' => 1]" />
            @endcan
        </x-slot:actions>
    </x-page-header>

    @include('cadence.partials.filters', [
        'action' => route('cadence.upcoming'),
        'filters' => $filters,
        'withRange' => true,
    ])

    <x-table :label="__('cadence.upcoming.title')">
        <x-slot:head>
            <x-table.sort-heading column="next_send_at">{{ __('cadence.columns.next_send_at') }}</x-table.sort-heading>
            <x-table.sort-heading column="client">{{ __('cadence.columns.client') }}</x-table.sort-heading>
            <x-table.heading>{{ __('cadence.columns.recipient') }}</x-table.heading>
            <x-table.sort-heading column="template">{{ __('cadence.columns.template') }}</x-table.sort-heading>
            <x-table.sort-heading column="frequency">{{ __('cadence.columns.frequency') }}</x-table.sort-heading>
            <x-table.heading>{{ __('cadence.columns.state') }}</x-table.heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.actions.view') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($schedules as $schedule)
            <x-table.row>
                <x-table.cell>
                    <span class="font-medium"><x-datetime :value="$schedule->next_send_at" /></span>
                </x-table.cell>

                <x-table.cell>
                    <a href="{{ route('clients.show', $schedule->client) }}" class="focus-ring rounded-control transition hover:text-primary">
                        {{ $schedule->client->name }}
                    </a>
                </x-table.cell>

                <x-table.cell muted>{{ $schedule->client->email }}</x-table.cell>
                <x-table.cell>{{ $schedule->template->label() }}</x-table.cell>
                <x-table.cell muted>{{ $schedule->frequency->label() }}</x-table.cell>

                <x-table.cell>
                    @include('cadence.partials.schedule-state', ['schedule' => $schedule])
                </x-table.cell>

                <x-table.cell align="right">
                    @include('cadence.partials.schedule-actions', ['schedule' => $schedule])
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="7">
                    <x-empty-state :title="$filters->isActive() ? __('cadence.upcoming.filtered_empty') : __('cadence.upcoming.empty')"
                                   :description="$filters->isActive() ? __('cadence.upcoming.filtered_empty_description') : __('cadence.upcoming.empty_description')"
                                   icon="calendar" />
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$schedules" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
