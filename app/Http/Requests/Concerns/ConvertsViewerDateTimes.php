<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Support\ViewerTimezone;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Turns a `datetime-local` field, which a user types in their own timezone, into UTC
 * before validation runs.
 *
 * Converting first is what lets rules such as `after:now` compare the instant the
 * user actually meant.
 */
trait ConvertsViewerDateTimes
{
    protected function toUtcDateTime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return $this->container->make(ViewerTimezone::class)->toUtc($value)->toIso8601String();
        } catch (InvalidFormatException) {
            // Leave the original value in place so the `date` rule reports it.
            return $value;
        }
    }
}
