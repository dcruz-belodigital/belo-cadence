<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ClientNotifications\ClientNotificationScheduleFilters;
use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use Carbon\CarbonImmutable;
use Database\Factories\ClientNotificationScheduleFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A recurring client email configuration: which template a client receives, and when.
 *
 * @property int $id
 * @property int $client_id
 * @property ClientEmailTemplate $template
 * @property ClientNotificationFrequency $frequency
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $next_send_at
 * @property CarbonImmutable|null $last_sent_at
 * @property bool $is_enabled
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Client $client
 * @property-read Collection<int, ClientNotificationDelivery> $deliveries
 * @property-read int|null $deliveries_count
 */
#[Fillable([
    'client_id',
    'template',
    'frequency',
    'starts_at',
    'next_send_at',
    'last_sent_at',
    'is_enabled',
])]
final class ClientNotificationSchedule extends Model
{
    /** @use HasFactory<ClientNotificationScheduleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The client this schedule belongs to.
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
     * @return HasMany<ClientNotificationDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(ClientNotificationDelivery::class);
    }

    /**
     * A one-time schedule that already ran keeps its history but is no longer active.
     */
    public function isCompleted(): bool
    {
        return $this->frequency === ClientNotificationFrequency::OneTime
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
            ->whereHas('client', function (Builder $client): void {
                $client->active()->whereNull('deleted_at');
            });
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
    protected function filtered(Builder $query, ClientNotificationScheduleFilters $filters): void
    {
        $query
            ->when($filters->scheduledOnly, fn (Builder $schedules): Builder => $schedules->whereNotNull('next_send_at'))
            ->when($filters->dueBefore, fn (Builder $schedules, DateTimeInterface $dueBefore): Builder => $schedules
                ->where('next_send_at', '<=', $dueBefore))
            ->when($filters->search, fn (Builder $schedules, string $search): Builder => $schedules
                ->whereHas('client', fn (Builder $client): Builder => $client->where(
                    fn (Builder $match): Builder => $match
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                )))
            ->when($filters->template, fn (Builder $schedules, ClientEmailTemplate $template): Builder => $schedules
                ->where('template', $template))
            ->when($filters->frequency, fn (Builder $schedules, ClientNotificationFrequency $frequency): Builder => $schedules
                ->where('frequency', $frequency))
            ->when($filters->isEnabled !== null, fn (Builder $schedules): Builder => $schedules
                ->where('is_enabled', $filters->isEnabled));

        if ($filters->sort === 'client') {
            $query->orderBy(
                Client::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('clients.id', 'client_notification_schedules.client_id'),
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
            'template' => ClientEmailTemplate::class,
            'frequency' => ClientNotificationFrequency::class,
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
