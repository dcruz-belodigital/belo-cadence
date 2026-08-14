<?php

declare(strict_types=1);

namespace App\Data\Clients;

use App\Enums\ClientStatus;
use Illuminate\Http\Request;

/**
 * The state of the client table: search, filters and sorting.
 *
 * Values are read from the query string and coerced to something the application
 * understands, so a hand-edited URL can never reach the database as-is. The same
 * object drives both the table and its export, which is what keeps
 * "export this view" honest.
 */
final readonly class ClientFilters
{
    /**
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['name', 'email', 'status', 'created_at'];

    public function __construct(
        public ?string $search = null,
        public ?ClientStatus $status = null,
        public bool $includeArchived = false,
        public string $sort = 'name',
        public string $direction = 'asc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();

        return new self(
            search: $request->filled('search') ? $request->string('search')->trim()->value() : null,
            status: ClientStatus::tryFrom($request->string('status')->toString()),
            includeArchived: $request->boolean('archived'),
            sort: in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'name',
            direction: $direction === 'desc' ? 'desc' : 'asc',
        );
    }

    public function isActive(): bool
    {
        return $this->search !== null
            || $this->status instanceof ClientStatus
            || $this->includeArchived;
    }
}
