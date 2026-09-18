<?php

declare(strict_types=1);

namespace App\Data\ClientAttributes;

use App\Enums\ClientAttributeType;
use Illuminate\Http\Request;

/**
 * The state of the client attribute table: search, filters and sorting.
 */
final readonly class ClientAttributeFilters
{
    /**
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['position', 'name', 'type'];

    public function __construct(
        public ?string $search = null,
        public ?ClientAttributeType $type = null,
        public ?bool $isActive = null,
        public string $sort = 'position',
        public string $direction = 'asc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();
        $state = $request->string('state')->toString();

        return new self(
            search: $request->filled('search') ? $request->string('search')->trim()->value() : null,
            type: ClientAttributeType::tryFrom($request->string('type')->toString()),
            isActive: match ($state) {
                'active' => true,
                'inactive' => false,
                default => null,
            },
            sort: in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'position',
            direction: $direction === 'desc' ? 'desc' : 'asc',
        );
    }

    public function isActive(): bool
    {
        return $this->search !== null
            || $this->type instanceof ClientAttributeType
            || $this->isActive !== null;
    }

    public function state(): string
    {
        return match ($this->isActive) {
            true => 'active',
            false => 'inactive',
            null => '',
        };
    }
}
