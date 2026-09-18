<?php

declare(strict_types=1);

namespace App\Data\Settings;

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;

final readonly class DefaultClientNotificationData
{
    public function __construct(
        public EmailTemplate $template,
        public NotificationFrequency $frequency,
        public bool $isEnabledByDefault,
    ) {}
}
