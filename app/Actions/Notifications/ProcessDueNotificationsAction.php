<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Data\Notifications\ProcessDueNotificationsResult;
use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;

/**
 * Finds the schedules whose occurrence has arrived and hands each one to the sender.
 *
 * Eligibility is decided by the `due` scope: the schedule is enabled, it has a next
 * occurrence, that occurrence has arrived, and — for a schedule that targets a client —
 * that client still exists, is active and has not been archived. A schedule that targets
 * its own recipient list answers to nobody's status.
 *
 * One occurrence per schedule is processed per run. A schedule that fell behind while
 * nothing was running therefore catches up one occurrence at a time instead of sending
 * a burst of email at once. Within one occurrence every recipient is sent to, so the
 * counts below are counts of emails.
 */
final class ProcessDueNotificationsAction
{
    public function __construct(
        private readonly SendScheduledNotificationAction $sendScheduledNotification,
    ) {}

    public function __invoke(?CarbonImmutable $at = null): ProcessDueNotificationsResult
    {
        $at ??= CarbonImmutable::now();

        $sent = 0;
        $failed = 0;
        $alreadyHandled = 0;
        $skipped = 0;

        $due = NotificationSchedule::query()
            ->due($at)
            ->with('client')
            ->orderBy('next_send_at')
            ->get();

        foreach ($due as $schedule) {
            $occurrence = $schedule->next_send_at;

            if ($occurrence === null) {
                continue;
            }

            $deliveries = ($this->sendScheduledNotification)($schedule, $occurrence);

            if ($deliveries === null) {
                $alreadyHandled++;

                continue;
            }

            if ($deliveries->isEmpty()) {
                $skipped++;

                continue;
            }

            foreach ($deliveries as $delivery) {
                $delivery->status === NotificationDeliveryStatus::Sent
                    ? $sent++
                    : $failed++;
            }
        }

        return new ProcessDueNotificationsResult($sent, $failed, $alreadyHandled, $skipped);
    }
}
