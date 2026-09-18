<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\ClientAttributeField;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A repeater's sub-field tree, read back as value objects all the way down.
 *
 * @implements CastsAttributes<list<ClientAttributeField>, list<ClientAttributeField>|array<int, mixed>>
 */
final class AsClientAttributeFields implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<ClientAttributeField>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return ClientAttributeField::listFromArray(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException("The [{$key}] attribute must be a list of repeater fields.");
        }

        $fields = array_values(array_filter(
            $value,
            static fn (mixed $field): bool => $field instanceof ClientAttributeField,
        ));

        // Anything that is not already a value object is normalised through the same path.
        if (count($fields) !== count($value)) {
            $fields = ClientAttributeField::listFromArray($value);
        }

        return (string) json_encode(ClientAttributeField::listToArray($fields));
    }
}
