<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\ClientAttributeChoices;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<ClientAttributeChoices, ClientAttributeChoices|array<int, mixed>>
 */
final class AsClientAttributeChoices implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ClientAttributeChoices
    {
        if ($value === null) {
            return new ClientAttributeChoices;
        }

        $decoded = json_decode((string) $value, true);

        return ClientAttributeChoices::fromArray(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ClientAttributeChoices) {
            return (string) json_encode($value->toArray());
        }

        if (is_array($value)) {
            return (string) json_encode(ClientAttributeChoices::fromArray($value)->toArray());
        }

        throw new InvalidArgumentException("The [{$key}] attribute must be a list of choices.");
    }
}
