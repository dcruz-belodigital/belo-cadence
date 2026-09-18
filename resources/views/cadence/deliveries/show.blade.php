<x-app-layout :heading="$delivery->subject"
               :back="route('cadence.deliveries.index')"
               :back-label="__('deliveries.title')">
    <x-page-header :description="$delivery->target_name ?? $delivery->client?->name" />


    <div class="space-y-6">
        @if ($delivery->is_manual)
            <x-alert variant="info">{{ __('deliveries.show.manual_notice') }}</x-alert>
        @endif

        @if ($delivery->status === \App\Enums\NotificationDeliveryStatus::Failed)
            <x-alert variant="danger" :title="__('deliveries.show.failure')">
                {{ $delivery->failure_message }}
            </x-alert>
        @endif

        <x-card :title="__('deliveries.show.details')">
            <x-detail-list>
                <x-detail-item :label="__('deliveries.columns.target')">
                    @if ($delivery->client !== null)
                        <a href="{{ route('clients.show', $delivery->client) }}" class="focus-ring rounded-control text-primary transition hover:underline">
                            {{ $delivery->target_name ?? $delivery->client->name }}
                        </a>
                    @else
                        {{ $delivery->target_name }}
                    @endif
                </x-detail-item>

                <x-detail-item :label="__('cadence.columns.target')">
                    <x-badge :variant="$delivery->target->badgeVariant()">{{ $delivery->target->label() }}</x-badge>
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.status')">
                    <x-badge :variant="$delivery->status->badgeVariant()">{{ $delivery->status->label() }}</x-badge>
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.source')">
                    <x-badge :variant="$delivery->source->badgeVariant()">{{ $delivery->source->label() }}</x-badge>
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.sent_by')">
                    {{-- Work the scheduler did belongs to no person, and is named as such. --}}
                    @if ($delivery->triggeredBy !== null)
                        {{ $delivery->triggeredBy->name }}
                    @else
                        <span class="text-foreground-muted">{{ __('deliveries.show.sent_by_system') }}</span>
                    @endif
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.recipient')">
                    {{ $delivery->recipient_name }} &lt;{{ $delivery->recipient_email }}&gt;
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.sender')">
                    {{ $delivery->sender_name }} &lt;{{ $delivery->sender_email }}&gt;
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.template')">{{ $delivery->template->label() }}</x-detail-item>

                <x-detail-item :label="__('deliveries.show.schedule')">
                    @if ($delivery->schedule === null)
                        <span class="text-foreground-muted">{{ __('deliveries.show.no_schedule') }}</span>
                    @elseif ($delivery->schedule->trashed())
                        <span class="text-foreground-muted">{{ __('deliveries.show.schedule_deleted') }}</span>
                    @else
                        <a href="{{ route('cadence.schedules.show', $delivery->schedule) }}" class="focus-ring rounded-control text-primary transition hover:underline">
                            {{ $delivery->schedule->frequency->label() }}
                        </a>
                    @endif
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.scheduled_for')">
                    <x-datetime :value="$delivery->scheduled_for" with-timezone />
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.attempted_at')">
                    <x-datetime :value="$delivery->attempted_at" />
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.sent_at')">
                    <x-datetime :value="$delivery->sent_at" />
                </x-detail-item>

                <x-detail-item :label="__('deliveries.columns.subject')" wide>{{ $delivery->subject }}</x-detail-item>
            </x-detail-list>
        </x-card>

        <x-card :title="__('deliveries.show.snapshot')" :description="__('deliveries.show.snapshot_notice')">
            @if ($delivery->body_html === '')
                <p class="text-body text-foreground-muted">{{ __('deliveries.show.no_body') }}</p>
            @else
                {{--
                    The stored message is rendered inside a sandboxed frame: it is historical
                    HTML and must not be able to interact with the application around it.
                    The frame keeps a plain light background because that is the canvas the
                    email itself was designed for, in either application theme.
                --}}
                <iframe title="{{ $delivery->subject }}"
                        sandbox
                        class="h-[32rem] w-full rounded-control border border-border bg-white"
                        srcdoc="{{ $delivery->body_html }}"></iframe>
            @endif
        </x-card>
    </div>
</x-app-layout>
