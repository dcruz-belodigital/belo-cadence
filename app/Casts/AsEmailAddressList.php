<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\EmailAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A stored list of email addresses, read back as value objects.
 *
 * The same normalisation as a single address applies to every entry, and the list is
 * de-duplicated, so a recipient cannot be added twice under two spellings.
 *
 * @implements CastsAttributes<list<EmailAddress>, iterable<EmailAddress|string>>
 */
final class AsEmailAddressList implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<EmailAddress>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $address): EmailAddress => new EmailAddress((string) $address),
            $decoded,
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_iterable($value)) {
            throw new InvalidArgumentException("The [{$key}] attribute must be a list of email addresses.");
        }

        $addresses = [];

        foreach ($value as $address) {
            $email = $address instanceof EmailAddress ? $address : new EmailAddress((string) $address);

            $addresses[$email->value] = $email->value;
        }

        return (string) json_encode(array_values($addresses));
    }
}
