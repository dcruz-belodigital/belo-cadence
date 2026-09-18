<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\TemplateBindings;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<TemplateBindings, TemplateBindings|array<string, mixed>>
 */
final class AsTemplateBindings implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): TemplateBindings
    {
        if ($value === null) {
            return new TemplateBindings;
        }

        $decoded = json_decode((string) $value, true);

        return TemplateBindings::fromArray(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof TemplateBindings) {
            return (string) json_encode($value->toArray());
        }

        if (is_array($value)) {
            return (string) json_encode(TemplateBindings::fromArray($value)->toArray());
        }

        throw new InvalidArgumentException("The [{$key}] attribute must be a set of template bindings.");
    }
}
