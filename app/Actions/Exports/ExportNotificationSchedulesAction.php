<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Data\Notifications\NotificationScheduleFilters;
use App\Enums\ExportMode;
use App\Models\NotificationSchedule;
use App\Support\Csv\CsvDocument;
use App\Support\ViewerTimezone;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;

final class ExportNotificationSchedulesAction
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
    ) {}

    public function __invoke(NotificationScheduleFilters $filters, ExportMode $mode): CsvDocument
    {
        return $mode === ExportMode::Raw
            ? $this->raw($filters)
            : $this->table($filters);
    }

    private function raw(NotificationScheduleFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'notification-schedules-raw.csv',
            headers: [
                'id', 'client_id', 'name', 'recipients', 'subject', 'template', 'frequency',
                'starts_at', 'next_send_at', 'last_sent_at', 'is_enabled', 'created_at', 'updated_at',
            ],
            rows: NotificationSchedule::query()
                ->filtered($filters)
                ->cursor()
                ->map(fn (NotificationSchedule $schedule): array => [
                    $schedule->getKey(),
                    $schedule->client_id,
                    $schedule->name,
                    implode(' ', array_map(
                        static fn (EmailAddress $recipient): string => $recipient->value,
                        $schedule->recipients ?? [],
                    )),
                    $schedule->subject,
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

    private function table(NotificationScheduleFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'notification-schedules.csv',
            headers: [
                __('cadence.columns.name'),
                __('cadence.columns.target'),
                __('cadence.columns.recipient'),
                __('cadence.columns.template'),
                __('cadence.columns.frequency'),
                __('cadence.columns.next_send_at'),
                __('cadence.columns.last_sent_at'),
                __('cadence.columns.state'),
            ],
            rows: NotificationSchedule::query()
                ->filtered($filters)
                ->with('client')
                ->cursor()
                ->map(fn (NotificationSchedule $schedule): array => [
                    $schedule->displayName(),
                    $schedule->target->label(),
                    // A client schedule has one address; a list schedule prints all of them.
                    implode(' ', array_map(
                        static fn (EmailAddress $recipient): string => $recipient->value,
                        $schedule->recipientAddresses(),
                    )),
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
