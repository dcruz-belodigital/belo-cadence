<?php

declare(strict_types=1);

namespace App\Actions\ClientNotifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\ClientNotificationSchedule;
use Illuminate\Support\Facades\DB;

/**
 * Stops a schedule from sending, keeping its anchor so it can be resumed later.
 */
final class DisableClientNotificationScheduleAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(ClientNotificationSchedule $schedule): ClientNotificationSchedule
    {
        return DB::transaction(function () use ($schedule): ClientNotificationSchedule {
            $schedule->update([
                'is_enabled' => false,
                'next_send_at' => null,
            ]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientNotificationScheduleDisabled,
                auditable: $schedule,
                newValues: ['is_enabled' => false],
            ));

            return $schedule;
        });
    }
}
