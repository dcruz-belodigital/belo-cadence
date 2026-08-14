<x-app-layout :heading="$schedule->template->label()"
               :back="route('clients.show', $schedule->client)"
               :back-label="$schedule->client->name">
    <x-page-header :description="$schedule->client->name">
        <x-slot:actions>
            @can('update', $schedule)
                <x-button :href="route('cadence.schedules.edit', $schedule)" variant="secondary" icon="pencil">
                    {{ __('common.actions.edit') }}
                </x-button>

                @if ($schedule->is_enabled)
                    <form method="POST" action="{{ route('cadence.schedules.disable', $schedule) }}">
                        @csrf
                        <x-button type="submit" variant="secondary">{{ __('cadence.actions.disable') }}</x-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('cadence.schedules.enable', $schedule) }}">
                        @csrf
                        <x-button type="submit" variant="secondary">{{ __('cadence.actions.enable') }}</x-button>
                    </form>
                @endif
            @endcan

            @can('delete', $schedule)
                <x-confirm-form :action="route('cadence.schedules.destroy', $schedule)"
                                method="DELETE"
                                :title="__('cadence.delete.title')"
                                :message="__('cadence.delete.message')"
                                :confirm="__('cadence.delete.confirm')"
                                trigger-variant="secondary"
                                trigger-size="md"
                                icon="trash">
                    <x-slot:trigger>{{ __('cadence.actions.delete') }}</x-slot:trigger>
                </x-confirm-form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        <x-card :title="__('cadence.show.details')">
            <x-detail-list>
                <x-detail-item :label="__('cadence.columns.client')">
                    <a href="{{ route('clients.show', $schedule->client) }}" class="focus-ring rounded-control text-primary transition hover:underline">
                        {{ $schedule->client->name }}
                    </a>
                </x-detail-item>

                <x-detail-item :label="__('cadence.columns.recipient')">{{ $schedule->client->email }}</x-detail-item>
                <x-detail-item :label="__('cadence.columns.template')">{{ $schedule->template->label() }}</x-detail-item>
                <x-detail-item :label="__('cadence.columns.frequency')">{{ $schedule->frequency->label() }}</x-detail-item>

                <x-detail-item :label="__('cadence.columns.starts_at')">
                    <x-datetime :value="$schedule->starts_at" with-timezone />
                </x-detail-item>

                <x-detail-item :label="__('cadence.columns.next_send_at')">
                    <x-datetime :value="$schedule->next_send_at" with-timezone />
                </x-detail-item>

                <x-detail-item :label="__('cadence.columns.last_sent_at')">
                    <x-datetime :value="$schedule->last_sent_at" />
                </x-detail-item>

                <x-detail-item :label="__('cadence.columns.state')">
                    @include('cadence.partials.schedule-state', ['schedule' => $schedule])
                </x-detail-item>
            </x-detail-list>
        </x-card>

        @if ($deliveries !== null)
            <x-card :title="__('cadence.show.deliveries')" flush>
                @if ($deliveries->isEmpty())
                    <x-empty-state :title="__('cadence.show.deliveries_empty')" icon="envelope" />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-body">
                            <thead class="border-b border-border bg-surface-sunken/50">
                                <tr>
                                    <x-table.heading>{{ __('deliveries.columns.scheduled_for') }}</x-table.heading>
                                    <x-table.heading>{{ __('deliveries.columns.subject') }}</x-table.heading>
                                    <x-table.heading>{{ __('deliveries.columns.status') }}</x-table.heading>
                                    <x-table.heading align="right"><span class="sr-only">{{ __('common.actions.view') }}</span></x-table.heading>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-border">
                                @foreach ($deliveries as $delivery)
                                    <x-table.row>
                                        <x-table.cell><x-datetime :value="$delivery->scheduled_for" /></x-table.cell>
                                        <x-table.cell muted>{{ $delivery->subject }}</x-table.cell>

                                        <x-table.cell>
                                            <x-badge :variant="$delivery->status->badgeVariant()">
                                                {{ $delivery->status->label() }}
                                            </x-badge>
                                        </x-table.cell>

                                        <x-table.cell align="right">
                                            @can('view', $delivery)
                                                <x-button :href="route('cadence.deliveries.show', $delivery)" variant="ghost" size="sm" icon="eye">
                                                    {{ __('common.actions.view') }}
                                                </x-button>
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
