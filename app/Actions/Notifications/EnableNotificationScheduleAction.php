<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\ApplicationSettings;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Turns a schedule back on and points it at its next future occurrence.
 *
 * Occurrences missed while the schedule was disabled are deliberately not sent: the
 * schedule resumes from now rather than catching up on the past.
 */
final class EnableNotificationScheduleAction
{
    public function __construct(
        private readonly CalculateNextNotificationDateAction $calculateNextNotificationDate,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(NotificationSchedule $schedule): NotificationSchedule
    {
        return DB::transaction(function () use ($schedule): NotificationSchedule {
            $schedule->update([
                'is_enabled' => true,
                'next_send_at' => ($this->calculateNextNotificationDate)(
                    $schedule->frequency,
                    $schedule->starts_at,
                    CarbonImmutable::now(),
                    ApplicationSettings::current()->default_timezone,
                ),
            ]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::NotificationScheduleEnabled,
                auditable: $schedule,
                newValues: [
                    'is_enabled' => true,
                    'next_send_at' => $schedule->next_send_at?->toIso8601String(),
                ],
            ));

            return $schedule;
        });
    }
}
