<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Audits\RecordAuditAction;
use App\Actions\Notifications\CreateNotificationScheduleAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Clients\CreateClientData;
use App\Data\Notifications\NotificationScheduleData;
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
        private readonly CreateNotificationScheduleAction $createSchedule,
        private readonly SyncClientAttributeValuesAction $syncAttributeValues,
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

            ($this->syncAttributeValues)($client, $data->attributeValues);

            // The client exists only now, so each default's target is filled in here.
            foreach ($data->notificationSchedules as $schedule) {
                ($this->createSchedule)(new NotificationScheduleData(
                    template: $schedule->template,
                    frequency: $schedule->frequency,
                    startsAt: $schedule->startsAt,
                    isEnabled: $schedule->isEnabled,
                    clientId: $client->getKey(),
                ));
            }

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientCreated,
                auditable: $client,
                newValues: $client->auditShape(),
                metadata: [
                    'notification_schedules' => array_map(
                        static fn (NotificationScheduleData $schedule): string => $schedule->template->value,
                        $data->notificationSchedules,
                    ),
                ],
            ));

            return $client;
        });
    }
}
