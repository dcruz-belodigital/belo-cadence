<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A file a notification was told to attach that could not be found.
 *
 * The client never answered that attribute, the answer has been cleared, or the attribute
 * has since been retired or deleted. Like an unfilled template value, this is caught by
 * `NotificationMailBuilder::prepare()` and recorded as a failed delivery: somebody chose
 * to attach that file, so an email arriving without it would be a quiet lie.
 */
final class UnresolvedAttachment extends RuntimeException
{
    public static function for(string $attachment): self
    {
        return new self((string) __('cadence.errors.unresolved_attachment', ['attachment' => $attachment]));
    }
}
