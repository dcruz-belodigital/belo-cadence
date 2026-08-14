<?php

declare(strict_types=1);

namespace App\Data\Audits;

use App\Enums\AuditAction;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;

final readonly class AuditFilters
{
    /**
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['created_at', 'action'];

    public function __construct(
        public ?string $search = null,
        public ?AuditAction $action = null,
        public ?string $auditableType = null,
        public ?string $fromDate = null,
        public ?string $toDate = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public string $sort = 'created_at',
        public string $direction = 'desc',
    ) {}

    public static function fromRequest(Request $request, TimezoneIdentifier $timezone): self
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();
        $fromDate = $request->filled('from') ? $request->string('from')->toString() : null;
        $toDate = $request->filled('to') ? $request->string('to')->toString() : null;

        return new self(
            search: $request->filled('search') ? $request->string('search')->trim()->value() : null,
            action: AuditAction::tryFrom($request->string('action')->toString()),
            auditableType: $request->filled('type') ? $request->string('type')->toString() : null,
            fromDate: $fromDate,
            toDate: $toDate,
            from: self::parse($fromDate, $timezone)?->startOfDay()->setTimezone('UTC'),
            to: self::parse($toDate, $timezone)?->endOfDay()->setTimezone('UTC'),
            sort: in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'created_at',
            direction: $direction === 'asc' ? 'asc' : 'desc',
        );
    }

    public function isActive(): bool
    {
        return $this->search !== null
            || $this->action instanceof AuditAction
            || $this->auditableType !== null
            || $this->from instanceof CarbonImmutable
            || $this->to instanceof CarbonImmutable;
    }

    private static function parse(?string $date, TimezoneIdentifier $timezone): ?CarbonImmutable
    {
        if ($date === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($date, $timezone->toDateTimeZone());
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
