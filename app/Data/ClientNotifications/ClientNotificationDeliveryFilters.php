<?php

declare(strict_types=1);

namespace App\Data\ClientNotifications;

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationDeliveryStatus;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Request;

/**
 * The state of the delivery history table.
 *
 * The date range is entered as plain dates and resolved to instants in the reader's
 * timezone, so "1 March" covers that whole day as the reader experienced it.
 */
final readonly class ClientNotificationDeliveryFilters
{
    /**
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['client', 'template', 'scheduled_for', 'attempted_at', 'sent_at', 'status'];

    public function __construct(
        public ?string $search = null,
        public ?int $clientId = null,
        public ?ClientEmailTemplate $template = null,
        public ?ClientNotificationDeliveryStatus $status = null,
        public ?string $fromDate = null,
        public ?string $toDate = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public string $sort = 'scheduled_for',
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
            clientId: $request->filled('client') ? $request->integer('client') : null,
            template: ClientEmailTemplate::tryFrom($request->string('template')->toString()),
            status: ClientNotificationDeliveryStatus::tryFrom($request->string('status')->toString()),
            fromDate: $fromDate,
            toDate: $toDate,
            from: self::startOfDay($fromDate, $timezone),
            to: self::endOfDay($toDate, $timezone),
            sort: in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'scheduled_for',
            direction: $direction === 'asc' ? 'asc' : 'desc',
        );
    }

    public function isActive(): bool
    {
        return $this->search !== null
            || $this->clientId !== null
            || $this->template instanceof ClientEmailTemplate
            || $this->status instanceof ClientNotificationDeliveryStatus
            || $this->from instanceof CarbonImmutable
            || $this->to instanceof CarbonImmutable;
    }

    private static function startOfDay(?string $date, TimezoneIdentifier $timezone): ?CarbonImmutable
    {
        return self::parse($date, $timezone)?->startOfDay()->setTimezone('UTC');
    }

    private static function endOfDay(?string $date, TimezoneIdentifier $timezone): ?CarbonImmutable
    {
        return self::parse($date, $timezone)?->endOfDay()->setTimezone('UTC');
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
