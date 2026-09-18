<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Data\Notifications\NotificationDeliveryFilters;
use App\Enums\ExportMode;
use App\Models\NotificationDelivery;
use App\Support\Csv\CsvDocument;
use App\Support\ViewerTimezone;
use Carbon\CarbonImmutable;

/**
 * Builds the delivery history CSV.
 *
 * The rendered email body is deliberately left out of both modes: it is long, it is
 * HTML, and it belongs on the delivery page where it can be read properly.
 */
final class ExportNotificationDeliveriesAction
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
    ) {}

    public function __invoke(NotificationDeliveryFilters $filters, ExportMode $mode): CsvDocument
    {
        return $mode === ExportMode::Raw
            ? $this->raw($filters)
            : $this->table($filters);
    }

    private function raw(NotificationDeliveryFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'delivery-history-raw.csv',
            headers: [
                'id', 'notification_schedule_id', 'client_id', 'target_name', 'is_manual',
                'triggered_by_user_id', 'template', 'recipient_email', 'recipient_name',
                'sender_email', 'sender_name', 'subject', 'scheduled_for', 'attempted_at',
                'sent_at', 'status', 'failure_message', 'created_at',
            ],
            rows: NotificationDelivery::query()
                ->filtered($filters)
                ->cursor()
                ->map(fn (NotificationDelivery $delivery): array => [
                    $delivery->getKey(),
                    $delivery->notification_schedule_id,
                    $delivery->client_id,
                    $delivery->target_name,
                    $delivery->is_manual,
                    $delivery->triggered_by_user_id,
                    $delivery->template->value,
                    $delivery->recipient_email->value,
                    $delivery->recipient_name,
                    $delivery->sender_email->value,
                    $delivery->sender_name,
                    $delivery->subject,
                    $delivery->scheduled_for->toIso8601String(),
                    $delivery->attempted_at->toIso8601String(),
                    $delivery->sent_at?->toIso8601String(),
                    $delivery->status->value,
                    $delivery->failure_message,
                    $delivery->created_at->toIso8601String(),
                ]),
        );
    }

    private function table(NotificationDeliveryFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'delivery-history.csv',
            headers: [
                __('deliveries.columns.target'),
                __('deliveries.columns.source'),
                __('deliveries.columns.template'),
                __('deliveries.columns.recipient'),
                __('deliveries.columns.subject'),
                __('deliveries.columns.scheduled_for'),
                __('deliveries.columns.sent_at'),
                __('deliveries.columns.status'),
                __('deliveries.columns.failure'),
            ],
            rows: NotificationDelivery::query()
                ->filtered($filters)
                ->with('client')
                ->cursor()
                ->map(fn (NotificationDelivery $delivery): array => [
                    $delivery->target_name ?? $delivery->client?->name,
                    $delivery->source->label(),
                    $delivery->template->label(),
                    $delivery->recipient_email->value,
                    $delivery->subject,
                    $this->formatDate($delivery->scheduled_for),
                    $this->formatDate($delivery->sent_at),
                    $delivery->status->label(),
                    $delivery->failure_message,
                ]),
        );
    }

    private function formatDate(?CarbonImmutable $value): ?string
    {
        return $value === null
            ? null
            : $this->viewerTimezone->format($value, (string) __('common.formats.datetime'));
    }
}
