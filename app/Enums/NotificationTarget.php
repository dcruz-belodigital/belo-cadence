<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a notification is addressed to.
 *
 * This is not a stored column: a schedule that holds a client targets that client, and
 * one that holds none targets its own list of addresses. Everything that has to name,
 * colour or validate the distinction goes through here.
 */
enum NotificationTarget: string
{
    case Client = 'client';
    case Recipients = 'recipients';

    public static function fromClientId(?int $clientId): self
    {
        return $clientId === null ? self::Recipients : self::Client;
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }

    public function label(): string
    {
        return __('enums.notification_target.'.$this->value);
    }

    public function badgeVariant(): BadgeVariant
    {
        return match ($this) {
            self::Client => BadgeVariant::Neutral,
            self::Recipients => BadgeVariant::Info,
        };
    }
}
