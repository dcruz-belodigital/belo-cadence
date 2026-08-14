<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientNotificationFrequency: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return __('enums.client_notification_frequency.'.$this->value);
    }

    public function isRecurring(): bool
    {
        return $this !== self::OneTime;
    }
}
