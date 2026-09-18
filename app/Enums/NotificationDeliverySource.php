<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a delivery came from.
 *
 * This is not a stored column: the delivery holds a plain `is_manual` flag, and this
 * enum is how that flag is filtered, labelled and exported without every table
 * inventing its own wording for it.
 */
enum NotificationDeliverySource: string
{
    case Scheduled = 'scheduled';
    case Manual = 'manual';

    public static function fromIsManual(bool $isManual): self
    {
        return $isManual ? self::Manual : self::Scheduled;
    }

    public function isManual(): bool
    {
        return $this === self::Manual;
    }

    public function label(): string
    {
        return __('enums.notification_delivery_source.'.$this->value);
    }

    public function badgeVariant(): BadgeVariant
    {
        return match ($this) {
            self::Manual => BadgeVariant::Info,
            self::Scheduled => BadgeVariant::Neutral,
        };
    }
}
