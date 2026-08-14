<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * Archives a client.
 *
 * The client is soft deleted and its schedules are switched off, so nothing is sent
 * on their behalf again, while every delivery already recorded stays exactly as it is.
 */
final class ArchiveClientAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(Client $client): void
    {
        DB::transaction(function () use ($client): void {
            $disabledSchedules = $client->notificationSchedules()->update([
                'is_enabled' => false,
                'next_send_at' => null,
            ]);

            $client->delete();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientArchived,
                auditable: $client,
                oldValues: [
                    'name' => $client->name,
                    'email' => $client->email->value,
                    'status' => $client->status->value,
                ],
                metadata: ['disabled_schedules' => $disabledSchedules],
            ));
        });
    }
}
