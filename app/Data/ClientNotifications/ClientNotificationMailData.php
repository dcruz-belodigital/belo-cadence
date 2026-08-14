<?php

declare(strict_types=1);

namespace App\Data\ClientNotifications;

use App\Enums\ClientEmailTemplate;
use App\Enums\Locale;
use App\ValueObjects\EmailAddress;

/**
 * Everything one outgoing client email needs, already prepared.
 *
 * The email views only print these values: no lookups, no formatting decisions and no
 * business rules live inside a Blade email.
 */
final readonly class ClientNotificationMailData
{
    public function __construct(
        public ClientEmailTemplate $template,
        public string $applicationName,
        public string $clientName,
        public EmailAddress $recipientEmail,
        public ?string $recipientName,
        public EmailAddress $senderEmail,
        public string $senderName,
        public string $subject,
        public string $scheduledForLabel,
        public Locale $locale,
    ) {}
}
