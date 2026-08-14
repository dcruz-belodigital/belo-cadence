<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use App\Models\Client;
use App\Models\ClientNotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientNotificationSchedule>
 */
final class ClientNotificationScheduleFactory extends Factory
{
    protected $model = ClientNotificationSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = CarbonImmutable::now()->addDays(5)->startOfHour();

        return [
            'client_id' => Client::factory(),
            'template' => fake()->randomElement(ClientEmailTemplate::cases()),
            'frequency' => ClientNotificationFrequency::Monthly,
            'starts_at' => $startsAt,
            'next_send_at' => $startsAt,
            'last_sent_at' => null,
            'is_enabled' => true,
        ];
    }

    public function template(ClientEmailTemplate $template): static
    {
        return $this->state(fn (array $attributes): array => ['template' => $template]);
    }

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => ClientNotificationFrequency::OneTime,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => ClientNotificationFrequency::Monthly,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => ClientNotificationFrequency::Yearly,
        ]);
    }

    /**
     * Switched off: it keeps its anchor but has no upcoming occurrence.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => false,
            'next_send_at' => null,
        ]);
    }

    /**
     * Its next occurrence has already arrived, so the next run will send it.
     */
    public function due(): static
    {
        return $this->state(function (array $attributes): array {
            $startsAt = CarbonImmutable::now()->subMinutes(5);

            return [
                'starts_at' => $startsAt,
                'next_send_at' => $startsAt,
                'is_enabled' => true,
            ];
        });
    }

    /**
     * Scheduled for a specific moment.
     */
    public function scheduledFor(CarbonImmutable $moment): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => $moment,
            'next_send_at' => $moment,
        ]);
    }

    /**
     * A one-time schedule that has already been sent and closed itself.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $sentAt = CarbonImmutable::now()->subDays(10);

            return [
                'frequency' => ClientNotificationFrequency::OneTime,
                'starts_at' => $sentAt,
                'next_send_at' => null,
                'last_sent_at' => $sentAt,
                'is_enabled' => false,
            ];
        });
    }
}
