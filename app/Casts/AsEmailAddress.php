<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\EmailAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<EmailAddress, EmailAddress|string>
 */
final class AsEmailAddress implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EmailAddress
    {
        if ($value === null) {
            return null;
        }

        return new EmailAddress((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof EmailAddress) {
            return $value->value;
        }

        if (is_string($value)) {
            return (new EmailAddress($value))->value;
        }

        throw new InvalidArgumentException("The [{$key}] attribute must be an email address.");
    }
}
