<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use App\Mail\NotificationMail;

/**
 * One rendered message, ready to be recorded and then sent.
 *
 * Rendering happens before anything is written, so the body can be snapshotted even
 * when it could not be produced: `$renderFailure` carries the reason, and `$bodyHtml`
 * is then empty.
 */
final readonly class PreparedNotificationMail
{
    public function __construct(
        public NotificationMailData $data,
        public NotificationMail $mail,
        public string $bodyHtml,
        public ?string $renderFailure = null,
    ) {}
}
