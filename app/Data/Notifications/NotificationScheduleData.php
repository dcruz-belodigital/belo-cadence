<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;

/**
 * The configuration of one recurring notification: what it sends, to whom, how often,
 * and when the recurrence is anchored.
 *
 * Exactly one target is filled in. A client schedule carries `$clientId` and nothing
 * else about its addressees, because the client's own address is where it goes; a
 * recipient-list schedule carries `$name` and `$recipients` instead.
 *
 * `$subject` and `$message` belong to the blank template alone and are null for every
 * template that brings its own wording.
 *
 * `$startsAt` is always in UTC; the timezone a user typed it in is resolved before the
 * data reaches here.
 */
final readonly class NotificationScheduleData
{
    /**
     * @param  list<EmailAddress>  $recipients
     */
    public function __construct(
        public EmailTemplate $template,
        public NotificationFrequency $frequency,
        public CarbonImmutable $startsAt,
        public bool $isEnabled,
        public ?int $clientId = null,
        public ?string $name = null,
        public array $recipients = [],
        public ?string $subject = null,
        public ?string $message = null,
    ) {}

    public function target(): NotificationTarget
    {
        return NotificationTarget::fromClientId($this->clientId);
    }
}
