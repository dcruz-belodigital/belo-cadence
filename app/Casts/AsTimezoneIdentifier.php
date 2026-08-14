<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\TimezoneIdentifier;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<TimezoneIdentifier, TimezoneIdentifier|string>
 */
final class AsTimezoneIdentifier implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?TimezoneIdentifier
    {
        if ($value === null) {
            return null;
        }

        return new TimezoneIdentifier((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof TimezoneIdentifier) {
            return $value->value;
        }

        if (is_string($value)) {
            return (new TimezoneIdentifier($value))->value;
        }

        throw new InvalidArgumentException("The [{$key}] attribute must be a timezone identifier.");
    }
}
