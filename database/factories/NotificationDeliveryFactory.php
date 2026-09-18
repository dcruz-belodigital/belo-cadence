<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationTarget;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
final class NotificationDeliveryFactory extends Factory
{
    protected $model = NotificationDelivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Occurrences are unique per schedule, so each generated delivery gets its own
        // minute offset.
        $scheduledFor = CarbonImmutable::now()->subMinutes(fake()->unique()->numberBetween(1, 500_000));

        return [
            'notification_schedule_id' => NotificationSchedule::factory(),
            'is_manual' => false,
            'triggered_by_user_id' => null,
            'client_id' => fn (array $attributes): ?int => NotificationSchedule::query()
                ->findOrFail($attributes['notification_schedule_id'])
                ->client_id,
            'template' => fn (array $attributes): mixed => NotificationSchedule::query()
                ->findOrFail($attributes['notification_schedule_id'])
                ->template,
            // Snapshotted from the schedule, exactly as a real send does. Declared after
            // the schedule so the id it reads has already been resolved.
            'target_name' => fn (array $attributes): ?string => NotificationSchedule::query()
                ->with('client')
                ->findOrFail($attributes['notification_schedule_id'])
                ->displayName(),
            'recipient_email' => fake()->unique()->safeEmail(),
            'recipient_name' => fake()->company(),
            'sender_email' => 'cadence@belo-cadence.test',
            'sender_name' => 'Belo Cadence',
            'subject' => fake()->sentence(4),
            'body_html' => '<p>'.fake()->sentence().'</p>',
            'scheduled_for' => $scheduledFor,
            'attempted_at' => $scheduledFor->addMinute(),
            'sent_at' => $scheduledFor->addMinute(),
            'status' => NotificationDeliveryStatus::Sent,
            'failure_message' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NotificationDeliveryStatus::Sent,
            'sent_at' => CarbonImmutable::parse($attributes['attempted_at']),
            'failure_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NotificationDeliveryStatus::Failed,
            'sent_at' => null,
            'failure_message' => 'Connection to the mail server timed out.',
        ]);
    }

    /**
     * Claimed but not yet resolved, as it is while an attempt is in flight.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NotificationDeliveryStatus::Pending,
            'sent_at' => null,
            'failure_message' => null,
        ]);
    }

    /**
     * A delivery for a recipient list: no client, and named by the list it went out under.
     */
    public function forRecipientList(string $name = 'Operations digest'): static
    {
        return $this->state(fn (array $attributes): array => [
            'notification_schedule_id' => NotificationSchedule::factory()->forRecipients($name),
            // Nobody's personal name is known for a list address.
            'recipient_name' => null,
        ]);
    }

    /**
     * A send somebody asked for by hand: no schedule, and a person behind it.
     */
    public function manual(?User $triggeredBy = null): static
    {
        return $this->state(function (array $attributes) use ($triggeredBy): array {
            // The client is created up front so the delivery's snapshotted name matches it,
            // which is what a real manual send records.
            $client = Client::factory()->create();

            return [
                'notification_schedule_id' => null,
                'is_manual' => true,
                'triggered_by_user_id' => $triggeredBy?->getKey() ?? User::factory(),
                'client_id' => $client->getKey(),
                'target_name' => $client->name,
                'recipient_name' => $client->name,
                'template' => fake()->randomElement(array_values(array_filter(
                    EmailTemplate::for(NotificationTarget::Client),
                    static fn (EmailTemplate $template): bool => $template->hasOwnCopy(),
                ))),
            ];
        });
    }

    public function forSchedule(NotificationSchedule $schedule): static
    {
        return $this->state(fn (array $attributes): array => [
            'notification_schedule_id' => $schedule->getKey(),
            'client_id' => $schedule->client_id,
            'template' => $schedule->template,
        ]);
    }
}
