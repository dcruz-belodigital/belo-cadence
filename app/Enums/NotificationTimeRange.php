<?php

declare(strict_types=1);

namespace App\Enums;

use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;

/**
 * The predefined windows the upcoming overview can be narrowed to.
 */
enum NotificationTimeRange: string
{
    case Today = 'today';
    case Next7Days = 'next_7_days';
    case Next30Days = 'next_30_days';

    public function label(): string
    {
        return __('cadence.ranges.'.$this->value);
    }

    /**
     * The last moment included in the range, read in the given timezone so "today"
     * means the reader's today.
     */
    public function endsAt(TimezoneIdentifier $timezone): CarbonImmutable
    {
        $now = CarbonImmutable::now($timezone->toDateTimeZone());

        return match ($this) {
            self::Today => $now->endOfDay()->setTimezone('UTC'),
            self::Next7Days => $now->addDays(6)->endOfDay()->setTimezone('UTC'),
            self::Next30Days => $now->addDays(29)->endOfDay()->setTimezone('UTC'),
        };
    }
}
