@php
    use App\Enums\NotificationTarget;

    $isClientSchedule = $schedule->target === NotificationTarget::Client;
@endphp

<x-app-layout :heading="$schedule->displayName()"
               :back="$isClientSchedule ? route('clients.show', $schedule->client) : route('cadence.schedules.index')"
               :back-label="$isClientSchedule ? $schedule->client->name : __('cadence.title')">
    <x-page-header :description="$schedule->template->label()">
        <x-slot:actions>
            @can('send', $schedule)
                <x-confirm-form :action="route('cadence.schedules.send', $schedule)"
                                method="POST"
                                :title="__('cadence.send.title')"
                                :message="__('cadence.send.message')"
                                :confirm="__('cadence.send.confirm')"
                                variant="primary"
                                trigger-variant="secondary"
                                trigger-size="md"
                                icon="send">
                    <x-slot:trigger>{{ __('cadence.actions.send') }}</x-slot:trigger>
                </x-confirm-form>
            @endcan

            @can('update', $schedule)
                <x-button :href="route('cadence.schedules.edit', $schedule)" variant="secondary" icon="pencil">
                    {{ __('common.actions.edit') }}
                </x-button>

                @if ($schedule->is_enabled)
                    <x-confirm-form :action="route('cadence.schedules.disable', $schedule)"
                                    method="POST"
                                    :title="__('cadence.disable.title')"
                                    :message="__('cadence.disable.message')"
                                    :confirm="__('cadence.disable.confirm')"
                                    variant="primary"
                                    trigger-variant="secondary"
                                    trigger-size="md"
                                    icon="x-circle">
                        <x-slot:trigger>{{ __('cadence.actions.disable') }}</x-slot:trigger>
                    </x-confirm-form>
                @else
                    <x-confirm-form :action="route('cadence.schedules.enable', $schedule)"
                                    method="POST"
                                    :title="__('cadence.enable.title')"
                                    :message="__('cadence.enable.message')"
                                    :confirm="__('cadence.enable.confirm')"
                                    variant="primary"
                                    trigger-variant="secondary"
                                    trigger-size="md"
                                    icon="check-circle">
                        <x-slot:trigger>{{ __('cadence.actions.enable') }}</x-slot:trigger>
                    </x-confirm-form>
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
                <x-detail-item :label="__('cadence.columns.target')">
                    <x-badge :variant="$schedule->target->badgeVariant()">{{ $schedule->target->label() }}</x-badge>
                </x-detail-item>

                @if ($isClientSchedule)
                    <x-detail-item :label="__('cadence.columns.client')">
                        <a href="{{ route('clients.show', $schedule->client) }}" class="focus-ring rounded-control text-primary transition hover:underline">
                            {{ $schedule->client->name }}
                        </a>
                    </x-detail-item>

                    <x-detail-item :label="__('cadence.columns.recipient')">{{ $schedule->client->email }}</x-detail-item>
                @else
                    <x-detail-item :label="__('cadence.fields.name')">{{ $schedule->name }}</x-detail-item>

                    <x-detail-item :label="__('cadence.show.recipients')" wide>
                        {{-- Every address gets its own copy, and its own line in delivery history. --}}
                        <ul class="space-y-0.5">
                            @foreach ($schedule->recipients ?? [] as $recipient)
                                <li class="font-mono text-meta">{{ $recipient }}</li>
                            @endforeach
                        </ul>
                    </x-detail-item>
                @endif

                <x-detail-item :label="__('cadence.columns.template')">{{ $schedule->template->label() }}</x-detail-item>
                <x-detail-item :label="__('cadence.columns.frequency')">{{ $schedule->frequency->label() }}</x-detail-item>

                @unless ($schedule->template->hasOwnCopy())
                    <x-detail-item :label="__('cadence.show.subject')" wide>{{ $schedule->subject }}</x-detail-item>

                    <x-detail-item :label="__('cadence.show.message')" wide>
                        <p class="whitespace-pre-line">{{ $schedule->message }}</p>
                    </x-detail-item>
                @endunless

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

                @if (! $schedule->attachment_bindings->isEmpty())
                    {{--
                        What this schedule attaches, named by where it reads them from
                        rather than by filename: the file itself is whatever the client
                        answers with at the moment each email goes out.
                    --}}
                    <x-detail-item :label="__('cadence.attachments.title')" wide>
                        <ul class="space-y-1">
                            @foreach ($attachmentLabels as $label)
                                <li class="flex items-center gap-2">
                                    <x-icon name="document" size="size-4" class="shrink-0 text-foreground-subtle" />
                                    <span class="truncate">{{ $label }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-detail-item>
                @endif
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
                                    <x-table.heading>{{ __('deliveries.columns.recipient') }}</x-table.heading>
                                    <x-table.heading>{{ __('deliveries.columns.subject') }}</x-table.heading>
                                    <x-table.heading>{{ __('deliveries.columns.status') }}</x-table.heading>
                                    <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-border">
                                @foreach ($deliveries as $delivery)
                                    <x-table.row>
                                        <x-table.cell><x-datetime :value="$delivery->scheduled_for" /></x-table.cell>
                                        <x-table.cell muted>{{ $delivery->recipient_email }}</x-table.cell>
                                        <x-table.cell muted>{{ $delivery->subject }}</x-table.cell>

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
