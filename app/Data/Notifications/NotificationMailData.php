<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use App\Enums\EmailTemplate;
use App\Enums\Locale;
use App\ValueObjects\EmailAddress;

/**
 * Everything one outgoing email needs, already prepared.
 *
 * The email views only print these values: no lookups, no formatting decisions and no
 * business rules live inside a Blade email.
 *
 * `$targetName` is what the message is about — a client's name, or the name of the
 * recipient list. `$message` is filled only by the blank template, which is the one
 * template whose wording is written on the schedule rather than in the source.
 */
final readonly class NotificationMailData
{
    public function __construct(
        public EmailTemplate $template,
        public string $applicationName,
        public ?string $targetName,
        public EmailAddress $recipientEmail,
        public ?string $recipientName,
        public EmailAddress $senderEmail,
        public string $senderName,
        public string $subject,
        public string $scheduledForLabel,
        public Locale $locale,
        public ?string $message = null,
    ) {}

    /**
     * The typed message split into the paragraphs it was written as.
     *
     * Blank content is entered as plain text and printed as text: the views escape it
     * like any other value, so no markup a person types can reach the email.
     *
     * @return list<string>
     */
    public function messageParagraphs(): array
    {
        if ($this->message === null || trim($this->message) === '') {
            return [];
        }

        $paragraphs = preg_split('/\R{2,}/', trim($this->message)) ?: [];

        return array_values(array_filter(array_map('trim', $paragraphs), static fn (string $p): bool => $p !== ''));
    }
}
