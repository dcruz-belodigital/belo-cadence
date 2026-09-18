<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\Models\Client;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationSchedule>
 */
final class NotificationScheduleFactory extends Factory
{
    protected $model = NotificationSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = CarbonImmutable::now()->addDays(5)->startOfHour();

        return [
            'client_id' => Client::factory(),
            'name' => null,
            'recipients' => null,
            'subject' => null,
            'message' => null,
            // A client schedule by default, and a template that brings its own wording:
            // the blank one needs a subject and a message, which `blank()` supplies.
            'template' => fake()->randomElement(array_values(array_filter(
                EmailTemplate::for(NotificationTarget::Client),
                static fn (EmailTemplate $template): bool => $template->hasOwnCopy(),
            ))),
            'frequency' => NotificationFrequency::Monthly,
            'starts_at' => $startsAt,
            'next_send_at' => $startsAt,
            'last_sent_at' => null,
            'is_enabled' => true,
        ];
    }

    public function template(EmailTemplate $template): static
    {
        return $this->state(fn (array $attributes): array => ['template' => $template]);
    }

    /**
     * A schedule that sends to its own named list of addresses instead of to a client.
     *
     * @param  list<string>|null  $recipients
     */
    public function forRecipients(?string $name = null, ?array $recipients = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_id' => null,
            'name' => $name ?? fake()->unique()->words(3, true),
            'recipients' => $recipients ?? [fake()->unique()->safeEmail(), fake()->unique()->safeEmail()],
            'template' => EmailTemplate::StatusUpdate,
        ]);
    }

    /**
     * The blank template, whose wording lives on the schedule rather than in the source.
     */
    public function blank(string $subject = 'A typed subject', string $message = "First paragraph.\n\nSecond paragraph."): static
    {
        return $this->state(fn (array $attributes): array => [
            'template' => EmailTemplate::Blank,
            'subject' => $subject,
            'message' => $message,
        ]);
    }

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => NotificationFrequency::OneTime,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => NotificationFrequency::Monthly,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => NotificationFrequency::Yearly,
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
                'frequency' => NotificationFrequency::OneTime,
                'starts_at' => $sentAt,
                'next_send_at' => null,
                'last_sent_at' => $sentAt,
                'is_enabled' => false,
            ];
        });
    }
}
