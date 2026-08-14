<?php

declare(strict_types=1);

namespace App\Rules;

use App\ValueObjects\EmailAddress;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * Validates an email address against the domain's own definition of a valid address.
 *
 * Using the value object here means request validation and persistence can never
 * disagree about what an email address is.
 */
final class EmailAddressRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('validation.email')->translate();

            return;
        }

        try {
            new EmailAddress($value);
        } catch (InvalidArgumentException) {
            $fail('validation.email')->translate();
        }
    }
}
