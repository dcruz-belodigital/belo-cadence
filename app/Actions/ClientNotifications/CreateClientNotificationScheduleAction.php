<?php

declare(strict_types=1);

namespace App\Actions\ClientNotifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\ClientNotifications\ClientNotificationScheduleData;
use App\Enums\AuditAction;
use App\Models\ApplicationSettings;
use App\Models\Client;
use App\Models\ClientNotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CreateClientNotificationScheduleAction
{
    public function __construct(
        private readonly CalculateNextNotificationDateAction $calculateNextNotificationDate,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(Client $client, ClientNotificationScheduleData $data): ClientNotificationSchedule
    {
        return DB::transaction(function () use ($client, $data): ClientNotificationSchedule {
            $schedule = ClientNotificationSchedule::query()->create([
                'client_id' => $client->getKey(),
                'template' => $data->template,
                'frequency' => $data->frequency,
                'starts_at' => $data->startsAt,
                'next_send_at' => ($this->calculateNextNotificationDate)(
                    $data->frequency,
                    $data->startsAt,
                    CarbonImmutable::now(),
                    ApplicationSettings::current()->default_timezone,
                ),
                'is_enabled' => $data->isEnabled,
            ]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientNotificationScheduleCreated,
                auditable: $schedule,
                newValues: [
                    'client_id' => $schedule->client_id,
                    'template' => $schedule->template->value,
                    'frequency' => $schedule->frequency->value,
                    'starts_at' => $schedule->starts_at->toIso8601String(),
                    'next_send_at' => $schedule->next_send_at?->toIso8601String(),
                    'is_enabled' => $schedule->is_enabled,
                ],
            ));

            return $schedule;
        });
    }
}
