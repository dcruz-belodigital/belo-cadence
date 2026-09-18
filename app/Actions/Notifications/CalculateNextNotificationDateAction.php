<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationFrequency;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;

/**
 * Works out when a schedule should next send.
 *
 * The recurrence is anchored to the day and time of `starts_at` as read in the given
 * timezone, so a schedule keeps its intended local time all year and the intended
 * day of the month is never lost:
 *
 *  - a monthly schedule anchored on the 31st falls back to the last day of shorter
 *    months and returns to the 31st afterwards;
 *  - a yearly schedule anchored on 29 February uses 28 February in common years and
 *    29 February again in leap years.
 */
final class CalculateNextNotificationDateAction
{
    /**
     * The first occurrence strictly after `$after`, or null when the schedule has no
     * further occurrence.
     */
    public function __invoke(
        NotificationFrequency $frequency,
        CarbonImmutable $anchor,
        CarbonImmutable $after,
        TimezoneIdentifier $timezone,
    ): ?CarbonImmutable {
        if ($frequency === NotificationFrequency::OneTime) {
            return $anchor->greaterThan($after) ? $anchor : null;
        }

        $anchorLocal = $anchor->setTimezone($timezone->toDateTimeZone());
        $afterLocal = $after->setTimezone($timezone->toDateTimeZone());

        $step = $this->firstCandidateStep($frequency, $anchorLocal, $afterLocal);

        // The estimate can land one step short, so walk forward until the candidate
        // really is in the future.
        while (true) {
            $candidate = $this->occurrence($frequency, $anchorLocal, $step);

            if ($candidate->greaterThan($afterLocal)) {
                return $candidate->setTimezone('UTC');
            }

            $step++;
        }
    }

    private function firstCandidateStep(
        NotificationFrequency $frequency,
        CarbonImmutable $anchorLocal,
        CarbonImmutable $afterLocal,
    ): int {
        $step = match ($frequency) {
            NotificationFrequency::Monthly => ($afterLocal->year - $anchorLocal->year) * 12
                + ($afterLocal->month - $anchorLocal->month),
            NotificationFrequency::Yearly => $afterLocal->year - $anchorLocal->year,
            NotificationFrequency::OneTime => 0,
        };

        return max(0, $step);
    }

    private function occurrence(
        NotificationFrequency $frequency,
        CarbonImmutable $anchorLocal,
        int $step,
    ): CarbonImmutable {
        $target = match ($frequency) {
            // Stepping from the first of the month can never overflow into the next one.
            NotificationFrequency::Monthly => $anchorLocal->startOfMonth()->addMonths($step),
            NotificationFrequency::Yearly => $anchorLocal->startOfMonth()->addYears($step),
            NotificationFrequency::OneTime => $anchorLocal->startOfMonth(),
        };

        return CarbonImmutable::create(
            $target->year,
            $target->month,
            min($anchorLocal->day, $target->daysInMonth),
            $anchorLocal->hour,
            $anchorLocal->minute,
            $anchorLocal->second,
            $anchorLocal->timezone,
        );
    }
}
