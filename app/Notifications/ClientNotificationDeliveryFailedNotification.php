<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ClientNotificationDelivery;
use Illuminate\Notifications\Notification;

/**
 * Tells the people who watch delivery history that a client email did not go out.
 *
 * This is an application notification for Belo Cadence users and has nothing to do
 * with the client emails the product sends.
 */
final class ClientNotificationDeliveryFailedNotification extends Notification
{
    public function __construct(
        private readonly ClientNotificationDelivery $delivery,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.delivery_failed.title'),
            'message' => __('notifications.delivery_failed.message', [
                'client' => $this->delivery->client->name,
                'template' => $this->delivery->template->label(),
            ]),
            'path' => route('cadence.deliveries.show', $this->delivery, absolute: false),
            'delivery_id' => $this->delivery->getKey(),
            'client_id' => $this->delivery->client_id,
            'variant' => 'danger',
        ];
    }
}
