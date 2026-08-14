<?php

declare(strict_types=1);

namespace App\Data\Settings;

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;

final readonly class DefaultClientNotificationData
{
    public function __construct(
        public ClientEmailTemplate $template,
        public ClientNotificationFrequency $frequency,
        public bool $isEnabledByDefault,
    ) {}
}
