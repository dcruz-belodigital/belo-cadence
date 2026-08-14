<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use App\Models\DefaultClientNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DefaultClientNotification>
 */
final class DefaultClientNotificationFactory extends Factory
{
    protected $model = DefaultClientNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template' => ClientEmailTemplate::GeneralReminder,
            'frequency' => ClientNotificationFrequency::Monthly,
            'is_enabled_by_default' => true,
        ];
    }

    public function forTemplate(ClientEmailTemplate $template, ClientNotificationFrequency $frequency): static
    {
        return $this->state(fn (array $attributes): array => [
            'template' => $template,
            'frequency' => $frequency,
        ]);
    }

    public function disabledByDefault(): static
    {
        return $this->state(fn (array $attributes): array => ['is_enabled_by_default' => false]);
    }
}
