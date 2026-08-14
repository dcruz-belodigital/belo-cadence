<?php

declare(strict_types=1);

namespace App\Data\ClientNotifications;

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use Carbon\CarbonImmutable;

/**
 * The configuration of one recurring client email: which template, how often, and
 * when the recurrence is anchored.
 *
 * `$startsAt` is always in UTC; the timezone a user typed it in is resolved before
 * the data reaches here.
 */
final readonly class ClientNotificationScheduleData
{
    public function __construct(
        public ClientEmailTemplate $template,
        public ClientNotificationFrequency $frequency,
        public CarbonImmutable $startsAt,
        public bool $isEnabled,
    ) {}
}
