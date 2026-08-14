<?php

declare(strict_types=1);

namespace App\Data\Settings;

use App\Enums\Locale;
use App\Enums\Theme;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\TimezoneIdentifier;

final readonly class ApplicationSettingsData
{
    public function __construct(
        public string $applicationName,
        public Locale $defaultLocale,
        public TimezoneIdentifier $defaultTimezone,
        public Theme $defaultTheme,
        public string $clientEmailSenderName,
        public EmailAddress $clientEmailSenderEmail,
    ) {}
}
