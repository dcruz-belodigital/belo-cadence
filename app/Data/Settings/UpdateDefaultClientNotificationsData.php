<?php

declare(strict_types=1);

namespace App\Data\Settings;

final readonly class UpdateDefaultClientNotificationsData
{
    /**
     * @param  list<DefaultClientNotificationData>  $entries
     */
    public function __construct(
        public array $entries,
    ) {}
}
