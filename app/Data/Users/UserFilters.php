<?php

declare(strict_types=1);

namespace App\Data\Users;

use Illuminate\Http\Request;

final readonly class UserFilters
{
    /**
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['name', 'email', 'created_at'];

    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public ?bool $isActive = null,
        public string $sort = 'name',
        public string $direction = 'asc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();
        $state = $request->string('state')->toString();

        return new self(
            search: $request->filled('search') ? $request->string('search')->trim()->value() : null,
            role: $request->filled('role') ? $request->string('role')->toString() : null,
            isActive: match ($state) {
                'active' => true,
                'inactive' => false,
                default => null,
            },
            sort: in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'name',
            direction: $direction === 'desc' ? 'desc' : 'asc',
        );
    }

    public function isActive(): bool
    {
        return $this->search !== null || $this->role !== null || $this->isActive !== null;
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
