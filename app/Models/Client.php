<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsEmailAddress;
use App\Data\Clients\ClientFilters;
use App\Enums\ClientStatus;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property EmailAddress $email
 * @property ClientStatus $status
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Collection<int, NotificationSchedule> $notificationSchedules
 * @property-read Collection<int, NotificationDelivery> $notificationDeliveries
 * @property-read int|null $notification_schedules_count
 */
#[Fillable(['name', 'email', 'status', 'notes'])]
final class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return HasMany<NotificationSchedule, $this>
     */
    public function notificationSchedules(): HasMany
    {
        return $this->hasMany(NotificationSchedule::class);
    }

    /**
     * Delivery history is kept directly on the client as well, so it survives the
     * removal of the schedule that produced it.
     *
     * @return HasMany<NotificationDelivery, $this>
     */
    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function isActive(): bool
    {
        return $this->status === ClientStatus::Active;
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ClientStatus::Active);
    }

    /**
     * Applies the client table's search, filters and sorting.
     *
     * Both the table and its CSV export use this, so an export always contains
     * exactly the rows the reader was looking at.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function filtered(Builder $query, ClientFilters $filters): void
    {
        $query
            ->when($filters->includeArchived, fn (Builder $clients): Builder => $clients->withTrashed())
            ->when($filters->search, fn (Builder $clients, string $search): Builder => $clients->where(
                fn (Builder $match): Builder => $match
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($filters->status, fn (Builder $clients, ClientStatus $status): Builder => $clients->where('status', $status))
            ->orderBy($filters->sort, $filters->direction);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => AsEmailAddress::class,
            'status' => ClientStatus::class,
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
