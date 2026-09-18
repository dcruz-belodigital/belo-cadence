<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\NotificationSchedule;
use Illuminate\Support\Facades\DB;

/**
 * Stops a schedule from sending, keeping its anchor so it can be resumed later.
 */
final class DisableNotificationScheduleAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(NotificationSchedule $schedule): NotificationSchedule
    {
        return DB::transaction(function () use ($schedule): NotificationSchedule {
            $schedule->update([
                'is_enabled' => false,
                'next_send_at' => null,
            ]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::NotificationScheduleDisabled,
                auditable: $schedule,
                newValues: ['is_enabled' => false],
            ));

            return $schedule;
        });
    }
}
