<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsEmailAddress;
use App\Data\Notifications\NotificationDeliveryFilters;
use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliverySource;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationTarget;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationDeliveryFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One email send attempt, snapshotted at the moment it was produced.
 *
 * Nothing here is ever recomputed from current client, sender or template data —
 * `target_name` included, so renaming a client or a recipient list never rewrites what
 * history says the message was for.
 *
 * A delivery comes either from a schedule or from a person: `is_manual` says which,
 * and a manual one carries no schedule and names the user who asked for it. It is
 * addressed either to a client or to one address from a recipient list; `target`
 * answers which, and there is one delivery per address either way.
 *
 * @property int $id
 * @property int|null $notification_schedule_id
 * @property int|null $client_id
 * @property string|null $target_name
 * @property bool $is_manual
 * @property int|null $triggered_by_user_id
 * @property EmailTemplate $template
 * @property EmailAddress $recipient_email
 * @property string|null $recipient_name
 * @property EmailAddress $sender_email
 * @property string $sender_name
 * @property string $subject
 * @property string $body_html
 * @property list<array{name: string, size: int}>|null $attachments
 * @property CarbonImmutable $scheduled_for
 * @property CarbonImmutable $attempted_at
 * @property CarbonImmutable|null $sent_at
 * @property NotificationDeliveryStatus $status
 * @property string|null $failure_message
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Client|null $client
 * @property-read NotificationSchedule|null $schedule
 * @property-read User|null $triggeredBy
 * @property-read NotificationDeliverySource $source
 * @property-read NotificationTarget $target
 */
#[Fillable([
    'notification_schedule_id',
    'client_id',
    'target_name',
    'is_manual',
    'triggered_by_user_id',
    'template',
    'recipient_email',
    'recipient_name',
    'sender_email',
    'sender_name',
    'subject',
    'body_html',
    'attachments',
    'scheduled_for',
    'attempted_at',
    'sent_at',
    'status',
    'failure_message',
])]
final class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    /**
     * The client this delivery was produced for, when it was for a client at all.
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
     * The schedule that produced this delivery, if one did.
     *
     * A manual send has no schedule at all, and a schedule may have been removed since;
     * history keeps pointing at it either way.
     *
     * @return BelongsTo<NotificationSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(NotificationSchedule::class, 'notification_schedule_id')
            ->withTrashed();
    }

    /**
     * The person who asked for this send, for a manual delivery.
     *
     * Null for everything the scheduler produced: that work belongs to no user.
     *
     * @return BelongsTo<User, $this>
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /**
     * What this delivery was addressed to.
     *
     * @return Attribute<NotificationTarget, never>
     */
    protected function target(): Attribute
    {
        return Attribute::get(fn (): NotificationTarget => NotificationTarget::fromClientId($this->client_id));
    }

    /**
     * Where this delivery came from, for anything that has to name or colour it.
     *
     * @return Attribute<NotificationDeliverySource, never>
     */
    protected function source(): Attribute
    {
        return Attribute::get(fn (): NotificationDeliverySource => NotificationDeliverySource::fromIsManual($this->is_manual));
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function failed(Builder $query): void
    {
        $query->where('status', NotificationDeliveryStatus::Failed);
    }

    /**
     * Deliveries a person asked for by hand rather than the scheduler.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function manual(Builder $query): void
    {
        $query->where('is_manual', true);
    }

    /**
     * Applies the delivery history table's search, filters and sorting.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function filtered(Builder $query, NotificationDeliveryFilters $filters): void
    {
        $query
            ->when($filters->search, fn (Builder $deliveries, string $search): Builder => $deliveries->where(
                fn (Builder $match): Builder => $match
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhere('recipient_email', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('target_name', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $client): Builder => $client
                        ->where('name', 'like', "%{$search}%"))
            ))
            ->when($filters->clientId, fn (Builder $deliveries, int $clientId): Builder => $deliveries
                ->where('client_id', $clientId))
            ->when($filters->template, fn (Builder $deliveries, EmailTemplate $template): Builder => $deliveries
                ->where('template', $template))
            ->when($filters->status, fn (Builder $deliveries, NotificationDeliveryStatus $status): Builder => $deliveries
                ->where('status', $status))
            ->when($filters->source, fn (Builder $deliveries, NotificationDeliverySource $source): Builder => $deliveries
                ->where('is_manual', $source->isManual()))
            ->when($filters->from, fn (Builder $deliveries, DateTimeInterface $from): Builder => $deliveries
                ->where('scheduled_for', '>=', $from))
            ->when($filters->to, fn (Builder $deliveries, DateTimeInterface $to): Builder => $deliveries
                ->where('scheduled_for', '<=', $to));

        // A delivery for a recipient list has no client, so it sorts by the name it was
        // sent under instead of sinking to one end of the table.
        if ($filters->sort === 'client') {
            $query->orderByRaw(
                'coalesce((select name from clients where clients.id = notification_deliveries.client_id), target_name) '
                .($filters->direction === 'asc' ? 'asc' : 'desc')
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
        $query->where('status', NotificationDeliveryStatus::Sent);
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
            'recipient_email' => AsEmailAddress::class,
            'sender_email' => AsEmailAddress::class,
            'status' => NotificationDeliveryStatus::class,
            'is_manual' => 'boolean',
            // A snapshot of what went out, so history survives the files being replaced.
            'attachments' => 'array',
            'scheduled_for' => 'immutable_datetime',
            'attempted_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
