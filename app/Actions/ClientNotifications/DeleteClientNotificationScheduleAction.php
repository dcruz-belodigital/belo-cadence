<?php

declare(strict_types=1);

namespace App\Actions\ClientNotifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\ClientNotificationSchedule;
use Illuminate\Support\Facades\DB;

/**
 * Removes a schedule from the working set.
 *
 * The record is soft deleted so the deliveries it already produced keep pointing at
 * the configuration that produced them.
 */
final class DeleteClientNotificationScheduleAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(ClientNotificationSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule): void {
            $schedule->update([
                'is_enabled' => false,
                'next_send_at' => null,
            ]);

            $schedule->delete();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientNotificationScheduleDeleted,
                auditable: $schedule,
                oldValues: [
                    'client_id' => $schedule->client_id,
                    'template' => $schedule->template->value,
                    'frequency' => $schedule->frequency->value,
                ],
            ));
        });
    }
}
