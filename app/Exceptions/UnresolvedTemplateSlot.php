<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A blank in an email template that could not be filled in.
 *
 * Thrown while the message is being rendered, which is before anything is written, so
 * `NotificationMailBuilder::prepare()` catches it exactly as it catches any other render
 * failure: the occurrence is still claimed and the schedule still moves on, and the
 * delivery is recorded as failed with this reason rather than an incomplete email going
 * out. Delivery history and the failure notification then say so.
 */
final class UnresolvedTemplateSlot extends RuntimeException
{
    public static function for(string $slot): self
    {
        return new self((string) __('cadence.errors.unresolved_slot', ['slot' => $slot]));
    }
}
