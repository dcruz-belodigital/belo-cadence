<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Data\ClientNotifications\ClientNotificationScheduleFilters;
use App\Enums\ExportMode;
use App\Models\ClientNotificationSchedule;
use App\Support\Csv\CsvDocument;
use App\Support\ViewerTimezone;
use Carbon\CarbonImmutable;

final class ExportClientNotificationSchedulesAction
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
    ) {}

    public function __invoke(ClientNotificationScheduleFilters $filters, ExportMode $mode): CsvDocument
    {
        return $mode === ExportMode::Raw
            ? $this->raw($filters)
            : $this->table($filters);
    }

    private function raw(ClientNotificationScheduleFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'notification-schedules-raw.csv',
            headers: [
                'id', 'client_id', 'template', 'frequency', 'starts_at',
                'next_send_at', 'last_sent_at', 'is_enabled', 'created_at', 'updated_at',
            ],
            rows: ClientNotificationSchedule::query()
                ->filtered($filters)
                ->cursor()
                ->map(fn (ClientNotificationSchedule $schedule): array => [
                    $schedule->getKey(),
                    $schedule->client_id,
                    $schedule->template->value,
                    $schedule->frequency->value,
                    $schedule->starts_at->toIso8601String(),
                    $schedule->next_send_at?->toIso8601String(),
                    $schedule->last_sent_at?->toIso8601String(),
                    $schedule->is_enabled ? '1' : '0',
                    $schedule->created_at->toIso8601String(),
                    $schedule->updated_at->toIso8601String(),
                ]),
        );
    }

    private function table(ClientNotificationScheduleFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'notification-schedules.csv',
            headers: [
                __('cadence.columns.client'),
                __('cadence.columns.recipient'),
                __('cadence.columns.template'),
                __('cadence.columns.frequency'),
                __('cadence.columns.next_send_at'),
                __('cadence.columns.last_sent_at'),
                __('cadence.columns.state'),
            ],
            rows: ClientNotificationSchedule::query()
                ->filtered($filters)
                ->with('client')
                ->cursor()
                ->map(fn (ClientNotificationSchedule $schedule): array => [
                    $schedule->client->name,
                    $schedule->client->email->value,
                    $schedule->template->label(),
                    $schedule->frequency->label(),
                    $this->formatDate($schedule->next_send_at),
                    $this->formatDate($schedule->last_sent_at),
                    $schedule->is_enabled
                        ? __('cadence.states.enabled')
                        : __('cadence.states.disabled'),
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
