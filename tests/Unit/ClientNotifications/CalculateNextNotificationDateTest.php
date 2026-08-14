<?php

declare(strict_types=1);

use App\Actions\ClientNotifications\CalculateNextNotificationDateAction;
use App\Enums\ClientNotificationFrequency;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;

function nextOccurrence(
    ClientNotificationFrequency $frequency,
    string $anchor,
    string $after,
    string $timezone = 'UTC',
): ?CarbonImmutable {
    return (new CalculateNextNotificationDateAction)(
        $frequency,
        CarbonImmutable::parse($anchor, $timezone)->setTimezone('UTC'),
        CarbonImmutable::parse($after, $timezone)->setTimezone('UTC'),
        new TimezoneIdentifier($timezone),
    );
}

describe('one-time schedules', function (): void {
    it('returns the anchor while it is still in the future', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::OneTime, '2026-09-15 09:00', '2026-09-01 00:00'))
            ->not->toBeNull()
            ->format('Y-m-d H:i')->toBe('2026-09-15 09:00');
    });

    it('has no occurrence left once the anchor has passed', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::OneTime, '2026-09-15 09:00', '2026-09-15 09:00'))
            ->toBeNull();
    });
});

describe('monthly schedules', function (): void {
    it('returns the anchor when it is still ahead', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2026-09-15 09:00', '2026-09-01 00:00')->format('Y-m-d H:i'))
            ->toBe('2026-09-15 09:00');
    });

    it('moves to the following month once the day has passed', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2026-09-15 09:00', '2026-09-15 09:00')->format('Y-m-d H:i'))
            ->toBe('2026-10-15 09:00');
    });

    it('keeps the time of day across months', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2026-01-10 17:45', '2026-06-01 00:00')->format('Y-m-d H:i'))
            ->toBe('2026-06-10 17:45');
    });

    it('uses the last day of a month that is too short', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2027-01-31 09:00', '2027-02-01 00:00')->format('Y-m-d'))
            ->toBe('2027-02-28');
    });

    it('uses 29 february in a leap year for a schedule anchored on the 31st', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2028-01-31 09:00', '2028-02-01 00:00')->format('Y-m-d'))
            ->toBe('2028-02-29');
    });

    it('returns to the original day after a short month', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2027-01-31 09:00', '2027-02-28 09:00')->format('Y-m-d'))
            ->toBe('2027-03-31');

        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2027-01-31 09:00', '2027-03-31 09:00')->format('Y-m-d'))
            ->toBe('2027-04-30');

        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2027-01-31 09:00', '2027-04-30 09:00')->format('Y-m-d'))
            ->toBe('2027-05-31');
    });

    it('never permanently becomes the 28th', function (): void {
        $anchor = '2027-01-31 09:00';
        $after = '2027-02-01 00:00';
        $days = [];

        foreach (range(1, 6) as $ignored) {
            $occurrence = nextOccurrence(ClientNotificationFrequency::Monthly, $anchor, $after);
            $days[] = $occurrence->format('Y-m-d');
            $after = $occurrence->format('Y-m-d H:i');
        }

        expect($days)->toBe([
            '2027-02-28',
            '2027-03-31',
            '2027-04-30',
            '2027-05-31',
            '2027-06-30',
            '2027-07-31',
        ]);
    });

    it('skips a long gap in one step', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Monthly, '2020-03-15 09:00', '2026-08-20 00:00')->format('Y-m-d H:i'))
            ->toBe('2026-09-15 09:00');
    });
});

describe('yearly schedules', function (): void {
    it('returns the anchor when it is still ahead', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Yearly, '2026-09-15 09:00', '2026-01-01 00:00')->format('Y-m-d H:i'))
            ->toBe('2026-09-15 09:00');
    });

    it('moves to the next year once the date has passed', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Yearly, '2026-09-15 09:00', '2026-09-15 09:00')->format('Y-m-d H:i'))
            ->toBe('2027-09-15 09:00');
    });

    it('uses 28 february in a common year for a schedule anchored on 29 february', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Yearly, '2028-02-29 09:00', '2028-03-01 00:00')->format('Y-m-d'))
            ->toBe('2029-02-28');
    });

    it('returns to 29 february when the next leap year arrives', function (): void {
        expect(nextOccurrence(ClientNotificationFrequency::Yearly, '2028-02-29 09:00', '2031-03-01 00:00')->format('Y-m-d'))
            ->toBe('2032-02-29');
    });

    it('walks a leap-year anchor through four years', function (): void {
        $anchor = '2028-02-29 09:00';
        $after = '2028-03-01 00:00';
        $dates = [];

        foreach (range(1, 4) as $ignored) {
            $occurrence = nextOccurrence(ClientNotificationFrequency::Yearly, $anchor, $after);
            $dates[] = $occurrence->format('Y-m-d');
            $after = $occurrence->format('Y-m-d H:i');
        }

        expect($dates)->toBe(['2029-02-28', '2030-02-28', '2031-02-28', '2032-02-29']);
    });
});

describe('timezones', function (): void {
    it('keeps the local time of day when the recurrence crosses a daylight saving change', function (): void {
        // 15 January and 15 July are on either side of the European summer time change.
        $winter = nextOccurrence(ClientNotificationFrequency::Monthly, '2027-01-15 09:00', '2027-06-20 00:00', 'Europe/Lisbon');

        expect($winter->setTimezone('Europe/Lisbon')->format('Y-m-d H:i'))->toBe('2027-07-15 09:00')
            ->and($winter->format('H:i'))->toBe('08:00');
    });

    it('anchors the day of the month in the given timezone', function (): void {
        // 01:30 in Tokyo on the 1st is still the previous month in UTC.
        $occurrence = nextOccurrence(ClientNotificationFrequency::Monthly, '2027-03-01 01:30', '2027-03-01 01:30', 'Asia/Tokyo');

        expect($occurrence->setTimezone('Asia/Tokyo')->format('Y-m-d H:i'))->toBe('2027-04-01 01:30');
    });
});
