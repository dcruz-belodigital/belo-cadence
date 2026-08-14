<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientNotificationDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return __('enums.client_notification_delivery_status.'.$this->value);
    }

    public function badgeVariant(): BadgeVariant
    {
        return match ($this) {
            self::Pending => BadgeVariant::Warning,
            self::Sent => BadgeVariant::Success,
            self::Failed => BadgeVariant::Danger,
        };
    }
}
