<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsEmailAddressList;
use App\Casts\AsTemplateAttachments;
use App\Casts\AsTemplateBindings;
use App\Data\Notifications\NotificationScheduleFilters;
use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\TemplateAttachments;
use App\ValueObjects\TemplateBindings;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationScheduleFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A recurring email configuration: which template goes out, to whom, and when.
 *
 * A schedule targets one of two things. With a client it sends to that client's address
 * and is tracked under them; without one it sends to its own named list of addresses,
 * which is how anything that is not about a single client — an internal digest, a
 * partner notice — is scheduled. `target` answers which, and `recipientAddresses()`
 * answers where the email actually goes in both cases.
 *
 * @property int $id
 * @property int|null $client_id
 * @property string|null $name
 * @property list<EmailAddress>|null $recipients
 * @property string|null $subject
 * @property string|null $message
 * @property TemplateBindings $template_bindings
 * @property TemplateAttachments $attachment_bindings
 * @property EmailTemplate $template
 * @property NotificationFrequency $frequency
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $next_send_at
 * @property CarbonImmutable|null $last_sent_at
 * @property bool $is_enabled
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Client|null $client
 * @property-read NotificationTarget $target
 * @property-read Collection<int, NotificationDelivery> $deliveries
 * @property-read int|null $deliveries_count
 */
#[Fillable([
    'client_id',
    'name',
    'recipients',
    'subject',
    'message',
    'template_bindings',
    'attachment_bindings',
    'template',
    'frequency',
    'starts_at',
    'next_send_at',
    'last_sent_at',
    'is_enabled',
])]
final class NotificationSchedule extends Model
{
    /** @use HasFactory<NotificationScheduleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The client this schedule belongs to, if it belongs to one.
     *
     * Archived clients are included so a schedule and its history remain readable
     * after the client has been archived. Queries that decide whether an email may be
     * sent state the requirement explicitly instead.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * @return HasMany<NotificationDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    /**
     * Whether this schedule sends to a client or to its own list of addresses.
     *
     * @return Attribute<NotificationTarget, never>
     */
    protected function target(): Attribute
    {
        return Attribute::get(fn (): NotificationTarget => NotificationTarget::fromClientId($this->client_id));
    }

    /**
     * What to call this schedule in a list, a heading or a delivery record.
     *
     * A list schedule is named by hand; a client schedule borrows the client's name, so
     * the `client` relation has to be loaded before asking.
     */
    public function displayName(): string
    {
        return $this->name ?? $this->client?->name ?? '';
    }

    /**
     * Where one occurrence of this schedule is actually sent.
     *
     * One email per address, so a client schedule sends one and a list schedule sends as
     * many as it has recipients.
     *
     * @return list<EmailAddress>
     */
    public function recipientAddresses(): array
    {
        if ($this->target === NotificationTarget::Client) {
            return $this->client instanceof Client ? [$this->client->email] : [];
        }

        return $this->recipients ?? [];
    }

    /**
     * A one-time schedule that already ran keeps its history but is no longer active.
     */
    public function isCompleted(): bool
    {
        return $this->frequency === NotificationFrequency::OneTime
            && $this->last_sent_at !== null;
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    /**
     * Schedules whose next occurrence has arrived, for clients that may be emailed.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function due(Builder $query, DateTimeInterface $at): void
    {
        $query->where('is_enabled', true)
            ->whereNotNull('next_send_at')
            ->where('next_send_at', '<=', $at)
            // A list schedule answers to nobody's status; a client schedule may only send
            // while its client is active and not archived.
            ->where(fn (Builder $schedules): Builder => $schedules
                ->whereNull('client_id')
                ->orWhereHas('client', function (Builder $client): void {
                    $client->active()->whereNull('deleted_at');
                }));
    }

    /**
     * Applies a schedule table's search, filters and sorting.
     *
     * The schedules table, the upcoming overview and their exports all go through
     * here, so an export always matches the view it was taken from.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function filtered(Builder $query, NotificationScheduleFilters $filters): void
    {
        $query
            ->when($filters->scheduledOnly, fn (Builder $schedules): Builder => $schedules->whereNotNull('next_send_at'))
            ->when($filters->dueBefore, fn (Builder $schedules, DateTimeInterface $dueBefore): Builder => $schedules
                ->where('next_send_at', '<=', $dueBefore))
            ->when($filters->search, fn (Builder $schedules, string $search): Builder => $schedules
                ->where(fn (Builder $match): Builder => $match
                    ->where('notification_schedules.name', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $client): Builder => $client->where(
                        fn (Builder $clientMatch): Builder => $clientMatch
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                    ))))
            ->when($filters->target, fn (Builder $schedules, NotificationTarget $target): Builder => $target->isClient()
                ? $schedules->whereNotNull('client_id')
                : $schedules->whereNull('client_id'))
            ->when($filters->template, fn (Builder $schedules, EmailTemplate $template): Builder => $schedules
                ->where('template', $template))
            ->when($filters->frequency, fn (Builder $schedules, NotificationFrequency $frequency): Builder => $schedules
                ->where('frequency', $frequency))
            ->when($filters->isEnabled !== null, fn (Builder $schedules): Builder => $schedules
                ->where('is_enabled', $filters->isEnabled));

        // Ordering "by client" means by whatever the schedule is called, so a list
        // schedule sorts by its own name rather than disappearing to one end.
        if ($filters->sort === 'client') {
            $query->orderBy(
                Client::query()
                    ->withTrashed()
                    ->selectRaw('coalesce(notification_schedules.name, clients.name)')
                    ->whereColumn('clients.id', 'notification_schedules.client_id'),
                $filters->direction,
            );

            return;
        }

        // Schedules without a next occurrence belong at the end of a date ordering.
        if ($filters->sort === 'next_send_at') {
            $query->orderByRaw('next_send_at is null');
        }

        $query->orderBy($filters->sort, $filters->direction);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template' => EmailTemplate::class,
            'recipients' => AsEmailAddressList::class,
            'template_bindings' => AsTemplateBindings::class,
            'attachment_bindings' => AsTemplateAttachments::class,
            'frequency' => NotificationFrequency::class,
            'starts_at' => 'immutable_datetime',
            'next_send_at' => 'immutable_datetime',
            'last_sent_at' => 'immutable_datetime',
            'is_enabled' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
