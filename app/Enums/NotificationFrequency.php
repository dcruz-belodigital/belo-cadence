<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationFrequency: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return __('enums.notification_frequency.'.$this->value);
    }

    public function isRecurring(): bool
    {
        return $this !== self::OneTime;
    }
}
