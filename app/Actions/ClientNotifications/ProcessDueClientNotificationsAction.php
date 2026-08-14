<?php

declare(strict_types=1);

namespace App\Actions\ClientNotifications;

use App\Data\ClientNotifications\ProcessDueNotificationsResult;
use App\Enums\ClientNotificationDeliveryStatus;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;
use Carbon\CarbonImmutable;

/**
 * Finds the schedules whose occurrence has arrived and hands each one to the sender.
 *
 * Eligibility is decided by the `due` scope: the schedule is enabled, it has a next
 * occurrence, that occurrence has arrived, and the client still exists, is active and
 * has not been archived.
 *
 * One occurrence per schedule is processed per run. A schedule that fell behind while
 * nothing was running therefore catches up one occurrence at a time instead of sending
 * a burst of email at once.
 */
final class ProcessDueClientNotificationsAction
{
    public function __construct(
        private readonly SendClientNotificationAction $sendClientNotification,
    ) {}

    public function __invoke(?CarbonImmutable $at = null): ProcessDueNotificationsResult
    {
        $at ??= CarbonImmutable::now();

        $sent = 0;
        $failed = 0;
        $alreadyHandled = 0;

        $due = ClientNotificationSchedule::query()
            ->due($at)
            ->with('client')
            ->orderBy('next_send_at')
            ->get();

        foreach ($due as $schedule) {
            $occurrence = $schedule->next_send_at;

            if ($occurrence === null) {
                continue;
            }

            $delivery = ($this->sendClientNotification)($schedule, $occurrence);

            if (! $delivery instanceof ClientNotificationDelivery) {
                $alreadyHandled++;

                continue;
            }

            $delivery->status === ClientNotificationDeliveryStatus::Sent
                ? $sent++
                : $failed++;
        }

        return new ProcessDueNotificationsResult($sent, $failed, $alreadyHandled);
    }
}
