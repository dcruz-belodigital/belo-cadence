<?php

declare(strict_types=1);

namespace App\Data\ClientAttributes;

use App\Enums\ClientAttributeType;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;

/**
 * A client attribute as somebody defined it.
 *
 * `key` and `type` are settled when the attribute is created and never written again:
 * the key names a CSV column, and retyping would leave every answer already recorded in
 * the shape of a type the attribute no longer has. `UpdateClientAttributeAction`
 * therefore ignores both.
 */
final readonly class ClientAttributeData
{
    /**
     * @param  list<ClientAttributeField>  $fields
     */
    public function __construct(
        public string $key,
        public string $name,
        public ClientAttributeType $type,
        public ?string $hint,
        public ClientAttributeChoices $options,
        public array $fields,
        public bool $isRequired,
        public bool $isActive,
        public int $position,
    ) {}
}
