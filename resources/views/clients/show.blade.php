@php
    use App\Models\NotificationSchedule;
@endphp

<x-app-layout :heading="$client->name"
               :back="route('clients.index')"
               :back-label="__('clients.title')">
    <x-page-header :description="(string) $client->email">
        <x-slot:actions>
            {{-- The policy already refuses an archived or inactive client, so no guard is repeated here. --}}
            @can('notify', $client)
                <x-button :href="route('cadence.deliveries.send', ['client' => $client->getKey()])" variant="secondary" icon="send">
                    {{ __('deliveries.actions.send') }}
                </x-button>
            @endcan

            @can('create', NotificationSchedule::class)
                @unless ($client->trashed())
                    <x-button :href="route('clients.schedules.create', $client)" variant="secondary" icon="plus">
                        {{ __('cadence.actions.create') }}
                    </x-button>
                @endunless
            @endcan

            @can('update', $client)
                @unless ($client->trashed())
                    <x-button :href="route('clients.edit', $client)" variant="secondary" icon="pencil">
                        {{ __('common.actions.edit') }}
                    </x-button>
                @endunless
            @endcan

            @if ($client->trashed())
                @can('restore', $client)
                    <x-confirm-form :action="route('clients.restore', $client)"
                                    method="POST"
                                    :title="__('clients.restore.title')"
                                    :message="__('clients.restore.message')"
                                    :confirm="__('clients.restore.confirm')"
                                    variant="primary"
                                    trigger-variant="primary"
                                    trigger-size="md"
                                    icon="restore">
                        <x-slot:trigger>{{ __('clients.actions.restore') }}</x-slot:trigger>
                    </x-confirm-form>
                @endcan
            @else
                @can('delete', $client)
                    <x-confirm-form :action="route('clients.destroy', $client)"
                                    method="DELETE"
                                    :title="__('clients.archive.title')"
                                    :message="__('clients.archive.message')"
                                    :confirm="__('clients.archive.confirm')"
                                    trigger-variant="secondary"
                                    trigger-size="md"
                                    icon="trash">
                        <x-slot:trigger>{{ __('clients.actions.archive') }}</x-slot:trigger>
                    </x-confirm-form>
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @if ($client->trashed())
            <x-alert variant="warning">{{ __('clients.show.archived_notice') }}</x-alert>
        @elseif (! $client->isActive())
            <x-alert variant="warning">{{ __('clients.show.inactive_notice') }}</x-alert>
        @endif

        <x-card :title="__('clients.show.details')">
            <x-detail-list>
                <x-detail-item :label="__('clients.fields.name')">{{ $client->name }}</x-detail-item>
                <x-detail-item :label="__('clients.fields.email')">{{ $client->email }}</x-detail-item>

                <x-detail-item :label="__('clients.fields.status')">
                    <x-badge :variant="$client->status->badgeVariant()">{{ $client->status->label() }}</x-badge>
                </x-detail-item>

                <x-detail-item :label="__('clients.columns.created')">
                    <x-datetime :value="$client->created_at" />
                </x-detail-item>

                @if ($client->notes !== null)
                    <x-detail-item :label="__('clients.show.notes')" wide>
                        <p class="whitespace-pre-line">{{ $client->notes }}</p>
                    </x-detail-item>
                @endif
            </x-detail-list>
        </x-card>

        @include('clients.partials.attribute-values')

        <x-card :title="__('clients.show.schedules')" :description="__('clients.show.schedules_description')" flush>
            @if ($client->notificationSchedules->isEmpty())
                <x-empty-state :title="__('clients.show.schedules_empty')"
                               :description="__('clients.show.schedules_empty_description')"
                               icon="calendar">
                    @can('create', NotificationSchedule::class)
                        @unless ($client->trashed())
                            <x-slot:actions>
                                <x-button :href="route('clients.schedules.create', $client)" icon="plus">
                                    {{ __('cadence.actions.create') }}
                                </x-button>
                            </x-slot:actions>
                        @endunless
                    @endcan
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-body">
                        <thead class="border-b border-border bg-surface-sunken/50">
                            <tr>
                                <x-table.heading>{{ __('cadence.columns.template') }}</x-table.heading>
                                <x-table.heading>{{ __('cadence.columns.frequency') }}</x-table.heading>
                                <x-table.heading>{{ __('cadence.columns.next_send_at') }}</x-table.heading>
                                <x-table.heading>{{ __('cadence.columns.last_sent_at') }}</x-table.heading>
                                <x-table.heading>{{ __('cadence.columns.state') }}</x-table.heading>
                                <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border">
                            @foreach ($client->notificationSchedules as $schedule)
                                <x-table.row>
                                    <x-table.cell>
                                        <a href="{{ route('cadence.schedules.show', $schedule) }}" class="focus-ring rounded-control font-medium transition hover:text-primary">
                                            {{ $schedule->template->label() }}
                                        </a>
                                    </x-table.cell>

                                    <x-table.cell muted>{{ $schedule->frequency->label() }}</x-table.cell>

                                    <x-table.cell>
                                        <x-datetime :value="$schedule->next_send_at" />
                                    </x-table.cell>

                                    <x-table.cell muted>
                                        <x-datetime :value="$schedule->last_sent_at" />
                                    </x-table.cell>

                                    <x-table.cell>
                                        @include('cadence.partials.schedule-state', ['schedule' => $schedule])
                                    </x-table.cell>

                                    <x-table.cell align="right">
                                        @include('cadence.partials.schedule-actions', ['schedule' => $schedule])
                                    </x-table.cell>
                                </x-table.row>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        @if ($deliveries !== null)
            <x-card :title="__('clients.show.deliveries')" :description="__('clients.show.deliveries_description')" flush>
                @if ($deliveries->isEmpty())
                    <x-empty-state :title="__('clients.show.deliveries_empty')" icon="envelope" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-body">
                            <thead class="border-b border-border bg-surface-sunken/50">
                                <tr>
                                    <x-table.heading>{{ __('deliveries.columns.subject') }}</x-table.heading>
                                    <x-table.heading>{{ __('deliveries.columns.source') }}</x-table.heading>
                                    <x-table.heading>{{ __('deliveries.columns.scheduled_for') }}</x-table.heading>
                                    <x-table.heading>{{ __('deliveries.columns.status') }}</x-table.heading>
                                    <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-border">
                                @foreach ($deliveries as $delivery)
                                    <x-table.row>
                                        <x-table.cell>{{ $delivery->subject }}</x-table.cell>

                                        <x-table.cell>
                                            <x-badge :variant="$delivery->source->badgeVariant()">
                                                {{ $delivery->source->label() }}
                                            </x-badge>
                                        </x-table.cell>

                                        <x-table.cell muted>
                                            <x-datetime :value="$delivery->scheduled_for" />
                                        </x-table.cell>

                                        <x-table.cell>
                                            <x-badge :variant="$delivery->status->badgeVariant()">
                                                {{ $delivery->status->label() }}
                                            </x-badge>
                                        </x-table.cell>

                                        <x-table.cell align="right">
                                            @can('view', $delivery)
                                                <x-table.actions>
                                                    <x-dropdown.item :href="route('cadence.deliveries.show', $delivery)" icon="eye">
                                                        {{ __('common.actions.view') }}
                                                    </x-dropdown.item>
                                                </x-table.actions>
                                            @endcan
                                        </x-table.cell>
                                    </x-table.row>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</x-app-layout>
