<?php

declare(strict_types=1);

namespace App\Data\Clients;

/**
 * What a client answered for each of its attributes, keyed by attribute id.
 *
 * **This is a patch, not a replacement.** An attribute that appears here is written, and
 * a null or empty answer clears it; an attribute that is *absent* is left exactly as it
 * was. Both callers depend on that difference:
 *
 *  - the client form renders every active attribute, so it always carries all of them and
 *    gets replacement behaviour for free;
 *  - the CSV import only carries the columns somebody mapped, so an unmapped attribute
 *    must survive the import untouched rather than being wiped from every matched client.
 */
final readonly class ClientAttributeValuesData
{
    /**
     * @param  array<int, mixed>  $values
     */
    public function __construct(
        public array $values = [],
    ) {}

    public static function none(): self
    {
        return new self;
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }
}
