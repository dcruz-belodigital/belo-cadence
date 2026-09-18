<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Notifications\NotificationScheduleData;
use App\Enums\AuditAction;
use App\Enums\NotificationTarget;
use App\Models\ApplicationSettings;
use App\Models\NotificationSchedule;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UpdateNotificationScheduleAction
{
    public function __construct(
        private readonly CalculateNextNotificationDateAction $calculateNextNotificationDate,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(
        NotificationSchedule $schedule,
        NotificationScheduleData $data,
    ): NotificationSchedule {
        return DB::transaction(function () use ($schedule, $data): NotificationSchedule {
            $before = $this->auditValues($schedule);

            $schedule->fill([
                'template' => $data->template,
                'frequency' => $data->frequency,
                'starts_at' => $data->startsAt,
                'is_enabled' => $data->isEnabled,
                'subject' => $data->subject,
                'message' => $data->message,
            ]);

            // A schedule never changes what it targets: the form that edits a client
            // schedule cannot turn it into a list, and the other way round.
            if ($schedule->target === NotificationTarget::Recipients) {
                $schedule->fill(['name' => $data->name, 'recipients' => $data->recipients]);
            }

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
                action: AuditAction::NotificationScheduleUpdated,
                auditable: $schedule,
                oldValues: $before,
                newValues: $this->auditValues($schedule),
            ));

            return $schedule;
        });
    }

    private function nextOccurrence(NotificationScheduleData $data): ?CarbonImmutable
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
    private function auditValues(NotificationSchedule $schedule): array
    {
        return [
            'name' => $schedule->name,
            'recipients' => array_map(static fn (EmailAddress $recipient): string => $recipient->value, $schedule->recipients ?? []),
            'template' => $schedule->template->value,
            'frequency' => $schedule->frequency->value,
            'starts_at' => $schedule->starts_at->toIso8601String(),
            'next_send_at' => $schedule->next_send_at?->toIso8601String(),
            'is_enabled' => $schedule->is_enabled,
        ];
    }
}
