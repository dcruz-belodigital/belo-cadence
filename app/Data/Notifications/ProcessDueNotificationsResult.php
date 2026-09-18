<?php

declare(strict_types=1);

namespace App\Data\Notifications;

/**
 * What one run of the due-notification processing did.
 *
 * The counts are per email, not per schedule: one occurrence of a schedule that targets
 * a recipient list sends one message per address. `$skipped` is an occurrence that was
 * spent without producing anything, which only happens to a schedule left with no
 * recipients at all.
 */
final readonly class ProcessDueNotificationsResult
{
    public function __construct(
        public int $sent = 0,
        public int $failed = 0,
        public int $alreadyHandled = 0,
        public int $skipped = 0,
    ) {}

    public function total(): int
    {
        return $this->sent + $this->failed + $this->alreadyHandled + $this->skipped;
    }
}
