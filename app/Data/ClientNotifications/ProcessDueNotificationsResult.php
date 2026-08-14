<?php

declare(strict_types=1);

namespace App\Data\ClientNotifications;

/**
 * What one run of the due-notification processing did.
 */
final readonly class ProcessDueNotificationsResult
{
    public function __construct(
        public int $sent = 0,
        public int $failed = 0,
        public int $alreadyHandled = 0,
    ) {}

    public function total(): int
    {
        return $this->sent + $this->failed + $this->alreadyHandled;
    }
}
