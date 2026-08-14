<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Audits\RecordAuditAction;
use App\Actions\ClientNotifications\CreateClientNotificationScheduleAction;
use App\Data\Audits\RecordAuditData;
use App\Data\ClientNotifications\ClientNotificationScheduleData;
use App\Data\Clients\CreateClientData;
use App\Enums\AuditAction;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * Creates a client and, when the user chose to apply them, its initial notification
 * schedules. The schedules are ordinary client schedules from that moment on.
 */
final class CreateClientAction
{
    public function __construct(
        private readonly CreateClientNotificationScheduleAction $createSchedule,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(CreateClientData $data): Client
    {
        return DB::transaction(function () use ($data): Client {
            $client = Client::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'status' => $data->status,
                'notes' => $data->notes,
            ]);

            foreach ($data->notificationSchedules as $schedule) {
                ($this->createSchedule)($client, $schedule);
            }

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientCreated,
                auditable: $client,
                newValues: [
                    'name' => $client->name,
                    'email' => $client->email->value,
                    'status' => $client->status->value,
                    'notes' => $client->notes,
                ],
                metadata: [
                    'notification_schedules' => array_map(
                        static fn (ClientNotificationScheduleData $schedule): string => $schedule->template->value,
                        $data->notificationSchedules,
                    ),
                ],
            ));

            return $client;
        });
    }
}
