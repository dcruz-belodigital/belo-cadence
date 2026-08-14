<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientNotificationDeliveryStatus;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientNotificationDelivery>
 */
final class ClientNotificationDeliveryFactory extends Factory
{
    protected $model = ClientNotificationDelivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Occurrences are unique per schedule, so each generated delivery gets its own
        // minute offset.
        $scheduledFor = CarbonImmutable::now()->subMinutes(fake()->unique()->numberBetween(1, 500_000));

        return [
            'client_notification_schedule_id' => ClientNotificationSchedule::factory(),
            'client_id' => fn (array $attributes): int => ClientNotificationSchedule::query()
                ->findOrFail($attributes['client_notification_schedule_id'])
                ->client_id,
            'template' => fn (array $attributes): mixed => ClientNotificationSchedule::query()
                ->findOrFail($attributes['client_notification_schedule_id'])
                ->template,
            'recipient_email' => fake()->unique()->safeEmail(),
            'recipient_name' => fake()->company(),
            'sender_email' => 'cadence@belo-cadence.test',
            'sender_name' => 'Belo Cadence',
            'subject' => fake()->sentence(4),
            'body_html' => '<p>'.fake()->sentence().'</p>',
            'scheduled_for' => $scheduledFor,
            'attempted_at' => $scheduledFor->addMinute(),
            'sent_at' => $scheduledFor->addMinute(),
            'status' => ClientNotificationDeliveryStatus::Sent,
            'failure_message' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClientNotificationDeliveryStatus::Sent,
            'sent_at' => CarbonImmutable::parse($attributes['attempted_at']),
            'failure_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClientNotificationDeliveryStatus::Failed,
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
            'status' => ClientNotificationDeliveryStatus::Pending,
            'sent_at' => null,
            'failure_message' => null,
        ]);
    }

    public function forSchedule(ClientNotificationSchedule $schedule): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_notification_schedule_id' => $schedule->getKey(),
            'client_id' => $schedule->client_id,
            'template' => $schedule->template,
        ]);
    }
}
