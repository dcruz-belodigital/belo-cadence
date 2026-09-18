<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Data\Notifications\NotificationDispatch;
use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Support\NotificationMailBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Sends one notification now, because somebody asked for it.
 *
 * The order of work matches the scheduler in {@see SendScheduledNotificationAction}:
 * every message is rendered first so its exact content can be stored even when sending
 * fails, the deliveries are written, and only then is the mail handed to the mailer,
 * outside any transaction.
 *
 * What is deliberately absent is the claim: there is no schedule to lock, no occurrence
 * to advance and nothing to guard against duplicates. Asking twice is two sends, which
 * is what somebody pressing the button twice means. A schedule can be the source of the
 * dispatch — "send this one now" — and sending it changes nothing about its recurrence.
 *
 * No audit entry is written. Sending is recorded in delivery history in far more detail
 * than the audit log could hold, and a manual delivery names the person who asked.
 */
final class SendManualNotificationAction
{
    public function __construct(
        private readonly NotificationMailBuilder $mailBuilder,
        private readonly DeliverNotificationAction $deliver,
    ) {}

    /**
     * @return Collection<int, NotificationDelivery>
     */
    public function __invoke(NotificationDispatch $dispatch): Collection
    {
        $actor = Auth::user();
        $sentAt = CarbonImmutable::now();

        /** @var Collection<int, NotificationDelivery> $deliveries */
        $deliveries = new Collection;

        foreach ($dispatch->recipients as $recipient) {
            $message = $this->mailBuilder->prepare($dispatch, $recipient, $sentAt);

            $delivery = NotificationDelivery::query()->create([
                // Sent by hand, so it belongs to no schedule; `is_manual` is what says so.
                'notification_schedule_id' => null,
                'client_id' => $dispatch->clientId,
                'target_name' => $dispatch->targetName,
                'is_manual' => true,
                'triggered_by_user_id' => $actor instanceof User ? $actor->getKey() : null,
                'template' => $message->data->template,
                'recipient_email' => $message->data->recipientEmail,
                'recipient_name' => $message->data->recipientName,
                'sender_email' => $message->data->senderEmail,
                'sender_name' => $message->data->senderName,
                'subject' => $message->data->subject,
                'body_html' => $message->bodyHtml,
                // Nothing scheduled this, so the occurrence is the moment it was asked for.
                'scheduled_for' => $sentAt,
                'attempted_at' => $sentAt,
                'status' => NotificationDeliveryStatus::Pending,
            ]);

            $deliveries->push(($this->deliver)($delivery, $message->mail, $message->renderFailure));
        }

        return $deliveries;
    }
}
