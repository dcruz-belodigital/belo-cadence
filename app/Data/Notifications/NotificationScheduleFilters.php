<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\Enums\NotificationTimeRange;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The state of a schedule table: search, filters and sorting.
 *
 * The same object serves the schedules table, the upcoming overview and their CSV
 * exports. `$target` narrows to the schedules that belong to a client, or to the ones
 * that send to their own recipient list. `$dueBefore` is resolved here rather than in the query so the reader's
 * timezone decides what "today" means.
 */
final readonly class NotificationScheduleFilters
{
    /**
     * @var list<string>
     */
    public const SORTABLE_COLUMNS = ['client', 'template', 'frequency', 'next_send_at', 'starts_at', 'last_sent_at'];

    public function __construct(
        public ?string $search = null,
        public ?EmailTemplate $template = null,
        public ?NotificationFrequency $frequency = null,
        public ?NotificationTarget $target = null,
        public ?bool $isEnabled = null,
        public ?NotificationTimeRange $range = null,
        public ?CarbonImmutable $dueBefore = null,
        public bool $scheduledOnly = false,
        public string $sort = 'next_send_at',
        public string $direction = 'asc',
    ) {}

    public static function fromRequest(
        Request $request,
        TimezoneIdentifier $timezone,
        bool $scheduledOnly = false,
    ): self {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();
        $range = NotificationTimeRange::tryFrom($request->string('range')->toString());
        $state = $request->string('state')->toString();

        return new self(
            search: $request->filled('search') ? $request->string('search')->trim()->value() : null,
            template: EmailTemplate::tryFrom($request->string('template')->toString()),
            frequency: NotificationFrequency::tryFrom($request->string('frequency')->toString()),
            target: NotificationTarget::tryFrom($request->string('target')->toString()),
            isEnabled: match ($state) {
                'enabled' => true,
                'disabled' => false,
                default => null,
            },
            range: $range,
            dueBefore: $range?->endsAt($timezone),
            scheduledOnly: $scheduledOnly,
            sort: in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'next_send_at',
            direction: $direction === 'desc' ? 'desc' : 'asc',
        );
    }

    public function isActive(): bool
    {
        return $this->search !== null
            || $this->template instanceof EmailTemplate
            || $this->frequency instanceof NotificationFrequency
            || $this->target instanceof NotificationTarget
            || $this->isEnabled !== null
            || $this->range instanceof NotificationTimeRange;
    }

    public function state(): string
    {
        return match ($this->isEnabled) {
            true => 'enabled',
            false => 'disabled',
            null => '',
        };
    }
}
