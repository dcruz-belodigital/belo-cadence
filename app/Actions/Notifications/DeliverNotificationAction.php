<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Mail\NotificationMail;
use App\Models\NotificationDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Hands one already-recorded delivery to the mailer and writes down what happened.
 *
 * This is the tail every send shares, scheduled or by hand. It runs outside any
 * transaction on purpose: the delivery is already recorded, so nothing can roll back
 * the evidence of a message that has left.
 *
 * A message that could not even be rendered is failed here too, without being sent, so
 * that one method decides what a delivery's outcome looks like.
 */
final class DeliverNotificationAction
{
    public function __construct(
        private readonly RecordFailedNotificationDeliveryAction $recordFailure,
    ) {}

    public function __invoke(
        NotificationDelivery $delivery,
        NotificationMail $mail,
        ?string $renderFailure = null,
    ): NotificationDelivery {
        if ($renderFailure !== null) {
            return ($this->recordFailure)($delivery, $renderFailure);
        }

        try {
            Mail::send($mail);
        } catch (Throwable $exception) {
            Log::error('Sending a notification failed.', [
                'notification_delivery_id' => $delivery->getKey(),
                'exception' => $exception,
            ]);

            return ($this->recordFailure)($delivery, $exception->getMessage());
        }

        $delivery->update([
            'status' => NotificationDeliveryStatus::Sent,
            'sent_at' => CarbonImmutable::now(),
        ]);

        return $delivery;
    }
}
