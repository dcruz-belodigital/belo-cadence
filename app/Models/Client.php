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
 * @property-read Collection<int, ClientAttributeValue> $attributeValues
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

    /**
     * What this client answered for each custom attribute.
     *
     * There is no row for an unanswered attribute, so this is only ever the answers
     * that exist — including answers to attributes that have since been retired.
     *
     * @return HasMany<ClientAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ClientAttributeValue::class);
    }

    /**
     * The client flattened to plain values for its audit entry, answers included.
     *
     * `load()` rather than a lazy read is what makes the before-and-after diff honest:
     * the update action takes a snapshot, writes, and takes another, and a relation left
     * loaded from the first call would make every attribute change read as a no-op.
     *
     * @return array<string, mixed>
     */
    public function auditShape(): array
    {
        $this->load('attributeValues.attribute');

        return [
            'name' => $this->name,
            'email' => $this->email->value,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'attributes' => $this->attributeValues
                ->sortBy(fn (ClientAttributeValue $value): int => $value->attribute->position)
                ->mapWithKeys(fn (ClientAttributeValue $value): array => [$value->attribute->key => $value->value])
                ->all(),
        ];
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
            ->orderBy($filters->sort, $filters->direction)
            // A second, unique key: the export pages by offset, so a non-unique sort
            // such as status could otherwise repeat or skip a client between pages.
            ->orderBy('id');
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
