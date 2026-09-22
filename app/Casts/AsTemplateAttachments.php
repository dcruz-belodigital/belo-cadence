<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\TemplateAttachments;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<TemplateAttachments, TemplateAttachments|array<int, mixed>>
 */
final class AsTemplateAttachments implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): TemplateAttachments
    {
        if ($value === null) {
            return new TemplateAttachments;
        }

        $decoded = json_decode((string) $value, true);

        return TemplateAttachments::fromArray(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof TemplateAttachments) {
            return (string) json_encode($value->toArray());
        }

        if (is_array($value)) {
            return (string) json_encode(TemplateAttachments::fromArray($value)->toArray());
        }

        throw new InvalidArgumentException("The [{$key}] attribute must be a set of template attachments.");
    }
}
