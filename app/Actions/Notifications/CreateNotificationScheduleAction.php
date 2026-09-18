<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Notifications\NotificationScheduleData;
use App\Enums\AuditAction;
use App\Models\ApplicationSettings;
use App\Models\NotificationSchedule;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CreateNotificationScheduleAction
{
    public function __construct(
        private readonly CalculateNextNotificationDateAction $calculateNextNotificationDate,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    /**
     * The target lives in the data rather than in a separate argument, because a
     * schedule may have no client to pass.
     */
    public function __invoke(NotificationScheduleData $data): NotificationSchedule
    {
        return DB::transaction(function () use ($data): NotificationSchedule {
            $schedule = NotificationSchedule::query()->create([
                'client_id' => $data->clientId,
                'name' => $data->name,
                'recipients' => $data->recipients,
                'subject' => $data->subject,
                'message' => $data->message,
                'template_bindings' => $data->templateBindings,
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
                action: AuditAction::NotificationScheduleCreated,
                auditable: $schedule,
                newValues: [
                    'client_id' => $schedule->client_id,
                    'name' => $schedule->name,
                    'recipients' => array_map(static fn (EmailAddress $recipient): string => $recipient->value, $schedule->recipients ?? []),
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
