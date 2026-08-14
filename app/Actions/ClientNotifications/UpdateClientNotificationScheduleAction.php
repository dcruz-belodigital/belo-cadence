<?php

declare(strict_types=1);

namespace App\Actions\ClientNotifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\ClientNotifications\ClientNotificationScheduleData;
use App\Enums\AuditAction;
use App\Models\ApplicationSettings;
use App\Models\ClientNotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UpdateClientNotificationScheduleAction
{
    public function __construct(
        private readonly CalculateNextNotificationDateAction $calculateNextNotificationDate,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(
        ClientNotificationSchedule $schedule,
        ClientNotificationScheduleData $data,
    ): ClientNotificationSchedule {
        return DB::transaction(function () use ($schedule, $data): ClientNotificationSchedule {
            $before = $this->auditValues($schedule);

            $schedule->fill([
                'template' => $data->template,
                'frequency' => $data->frequency,
                'starts_at' => $data->startsAt,
                'is_enabled' => $data->isEnabled,
            ]);

            $recurrenceMoved = $schedule->isDirty(['starts_at', 'frequency']);

            // An edit that leaves the recurrence alone keeps the occurrence that is
            // already queued, so changing a template never quietly reschedules an
            // upcoming email.
            $schedule->next_send_at = match (true) {
                ! $data->isEnabled => null,
                $recurrenceMoved, $schedule->next_send_at === null => $this->nextOccurrence($data),
                default => $schedule->next_send_at,
            };

            $schedule->save();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientNotificationScheduleUpdated,
                auditable: $schedule,
                oldValues: $before,
                newValues: $this->auditValues($schedule),
            ));

            return $schedule;
        });
    }

    private function nextOccurrence(ClientNotificationScheduleData $data): ?CarbonImmutable
    {
        return ($this->calculateNextNotificationDate)(
            $data->frequency,
            $data->startsAt,
            CarbonImmutable::now(),
            ApplicationSettings::current()->default_timezone,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(ClientNotificationSchedule $schedule): array
    {
        return [
            'template' => $schedule->template->value,
            'frequency' => $schedule->frequency->value,
            'starts_at' => $schedule->starts_at->toIso8601String(),
            'next_send_at' => $schedule->next_send_at?->toIso8601String(),
            'is_enabled' => $schedule->is_enabled,
        ];
    }
}
