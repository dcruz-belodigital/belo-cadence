<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsEmailAddress;
use App\Data\ClientNotifications\ClientNotificationDeliveryFilters;
use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationDeliveryStatus;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;
use Database\Factories\ClientNotificationDeliveryFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One client email send attempt, snapshotted at the moment it was produced.
 *
 * Nothing here is ever recomputed from current client, sender or template data.
 *
 * @property int $id
 * @property int $client_notification_schedule_id
 * @property int $client_id
 * @property ClientEmailTemplate $template
 * @property EmailAddress $recipient_email
 * @property string|null $recipient_name
 * @property EmailAddress $sender_email
 * @property string $sender_name
 * @property string $subject
 * @property string $body_html
 * @property CarbonImmutable $scheduled_for
 * @property CarbonImmutable $attempted_at
 * @property CarbonImmutable|null $sent_at
 * @property ClientNotificationDeliveryStatus $status
 * @property string|null $failure_message
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Client $client
 * @property-read ClientNotificationSchedule $schedule
 */
#[Fillable([
    'client_notification_schedule_id',
    'client_id',
    'template',
    'recipient_email',
    'recipient_name',
    'sender_email',
    'sender_name',
    'subject',
    'body_html',
    'scheduled_for',
    'attempted_at',
    'sent_at',
    'status',
    'failure_message',
])]
final class ClientNotificationDelivery extends Model
{
    /** @use HasFactory<ClientNotificationDeliveryFactory> */
    use HasFactory;

    /**
     * The client this delivery was produced for.
     *
     * Archived clients are included: history has to stay readable after a client has
     * been removed from the working list.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * The schedule may have been removed since; history keeps pointing at it.
     *
     * @return BelongsTo<ClientNotificationSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClientNotificationSchedule::class, 'client_notification_schedule_id')
            ->withTrashed();
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function failed(Builder $query): void
    {
        $query->where('status', ClientNotificationDeliveryStatus::Failed);
    }

    /**
     * Applies the delivery history table's search, filters and sorting.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function filtered(Builder $query, ClientNotificationDeliveryFilters $filters): void
    {
        $query
            ->when($filters->search, fn (Builder $deliveries, string $search): Builder => $deliveries->where(
                fn (Builder $match): Builder => $match
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhere('recipient_email', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $client): Builder => $client
                        ->where('name', 'like', "%{$search}%"))
            ))
            ->when($filters->clientId, fn (Builder $deliveries, int $clientId): Builder => $deliveries
                ->where('client_id', $clientId))
            ->when($filters->template, fn (Builder $deliveries, ClientEmailTemplate $template): Builder => $deliveries
                ->where('template', $template))
            ->when($filters->status, fn (Builder $deliveries, ClientNotificationDeliveryStatus $status): Builder => $deliveries
                ->where('status', $status))
            ->when($filters->from, fn (Builder $deliveries, DateTimeInterface $from): Builder => $deliveries
                ->where('scheduled_for', '>=', $from))
            ->when($filters->to, fn (Builder $deliveries, DateTimeInterface $to): Builder => $deliveries
                ->where('scheduled_for', '<=', $to));

        if ($filters->sort === 'client') {
            $query->orderBy(
                Client::query()
                    ->withTrashed()
                    ->select('name')
                    ->whereColumn('clients.id', 'client_notification_deliveries.client_id'),
                $filters->direction,
            );

            return;
        }

        $query->orderBy($filters->sort, $filters->direction);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function sent(Builder $query): void
    {
        $query->where('status', ClientNotificationDeliveryStatus::Sent);
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
            'recipient_email' => AsEmailAddress::class,
            'sender_email' => AsEmailAddress::class,
            'status' => ClientNotificationDeliveryStatus::class,
            'scheduled_for' => 'immutable_datetime',
            'attempted_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
