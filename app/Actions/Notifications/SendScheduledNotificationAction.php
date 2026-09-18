<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Data\Notifications\NotificationDispatch;
use App\Data\Notifications\PreparedNotificationMail;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationFrequency;
use App\Models\ApplicationSettings;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Support\NotificationMailBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sends one scheduled occurrence and records exactly what happened.
 *
 * The order of work is deliberate:
 *
 *  1. every recipient's message is rendered, so its exact content can be stored even if
 *     sending fails;
 *  2. the occurrence is claimed inside a transaction that locks the schedule, writes one
 *     delivery per recipient and advances the schedule, with a unique index on
 *     (schedule, occurrence, recipient) as the final word on duplicates;
 *  3. only then is each email handed to the mailer, outside any transaction, so a
 *     rollback can never erase the record of a message that has already left.
 *
 * A one-time schedule is closed when its occurrence is claimed rather than when the
 * sends succeed. That keeps the outcome deterministic and stops a failing send from
 * being retried endlessly; the failure is visible in history and raises an internal
 * notification instead.
 */
final class SendScheduledNotificationAction
{
    public function __construct(
        private readonly CalculateNextNotificationDateAction $calculateNextNotificationDate,
        private readonly NotificationMailBuilder $mailBuilder,
        private readonly DeliverNotificationAction $deliver,
    ) {}

    /**
     * Returns the deliveries that were recorded, one per recipient.
     *
     * Null means this occurrence had already been handled by somebody else. An empty
     * collection means it was claimed but there was nobody to send to, which only
     * happens to a schedule left with no recipients at all.
     *
     * @return Collection<int, NotificationDelivery>|null
     */
    public function __invoke(
        NotificationSchedule $schedule,
        CarbonImmutable $occurrence,
    ): ?Collection {
        $dispatch = NotificationDispatch::forSchedule($schedule);

        /** @var array<string, PreparedNotificationMail> $prepared */
        $prepared = [];

        foreach ($dispatch->recipients as $recipient) {
            $prepared[$recipient->value] = $this->mailBuilder->prepare($dispatch, $recipient, $occurrence);
        }

        $deliveries = $this->claimOccurrence($schedule, $occurrence, $dispatch, $prepared);

        if ($deliveries === null || $deliveries->isEmpty()) {
            return $deliveries;
        }

        $anySent = false;

        foreach ($deliveries as $delivery) {
            $message = $prepared[$delivery->recipient_email->value];

            $sent = ($this->deliver)($delivery, $message->mail, $message->renderFailure);

            $anySent = $anySent || $sent->status === NotificationDeliveryStatus::Sent;
        }

        // The schedule records its last send only if something actually went out.
        if ($anySent) {
            NotificationSchedule::query()
                ->whereKey($schedule->getKey())
                ->update(['last_sent_at' => $occurrence]);
        }

        return $deliveries;
    }

    /**
     * Claims the occurrence and moves the schedule on, or returns nothing when somebody
     * else already did.
     *
     * @param  array<string, PreparedNotificationMail>  $prepared
     * @return Collection<int, NotificationDelivery>|null
     */
    private function claimOccurrence(
        NotificationSchedule $schedule,
        CarbonImmutable $occurrence,
        NotificationDispatch $dispatch,
        array $prepared,
    ): ?Collection {
        try {
            return DB::transaction(function () use ($schedule, $occurrence, $dispatch, $prepared): ?Collection {
                /** @var Collection<int, NotificationDelivery> $deliveries */
                $deliveries = new Collection;

                $locked = NotificationSchedule::query()
                    ->whereKey($schedule->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $locked instanceof NotificationSchedule) {
                    return null;
                }

                $stillDue = $locked->is_enabled
                    && $locked->next_send_at !== null
                    && $locked->next_send_at->equalTo($occurrence);

                if (! $stillDue) {
                    return null;
                }

                foreach ($prepared as $message) {
                    $deliveries->push(NotificationDelivery::query()->create([
                        'notification_schedule_id' => $locked->getKey(),
                        'client_id' => $dispatch->clientId,
                        'target_name' => $dispatch->targetName,
                        'template' => $message->data->template,
                        'recipient_email' => $message->data->recipientEmail,
                        'recipient_name' => $message->data->recipientName,
                        'sender_email' => $message->data->senderEmail,
                        'sender_name' => $message->data->senderName,
                        'subject' => $message->data->subject,
                        'body_html' => $message->bodyHtml,
                        'scheduled_for' => $occurrence,
                        'attempted_at' => CarbonImmutable::now(),
                        'status' => NotificationDeliveryStatus::Pending,
                    ]));
                }

                if ($deliveries->isEmpty()) {
                    // Nothing to send, but the occurrence is spent: leaving it in place
                    // would make the scheduler retry a schedule with no recipients forever.
                    Log::warning('A due notification schedule has no recipients.', [
                        'notification_schedule_id' => $locked->getKey(),
                    ]);
                }

                $locked->update([
                    'next_send_at' => ($this->calculateNextNotificationDate)(
                        $locked->frequency,
                        $locked->starts_at,
                        $occurrence,
                        ApplicationSettings::current()->default_timezone,
                    ),
                    'is_enabled' => $locked->frequency === NotificationFrequency::OneTime
                        ? false
                        : $locked->is_enabled,
                ]);

                return $deliveries;
            });
        } catch (UniqueConstraintViolationException) {
            // Another process recorded this exact occurrence first.
            return null;
        }
    }
}
