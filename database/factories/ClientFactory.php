<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
final class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'email' => fake()->unique()->companyEmail(),
            'status' => ClientStatus::Active,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ClientStatus::Active]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ClientStatus::Inactive]);
    }

    public function withNotes(): static
    {
        return $this->state(fn (array $attributes): array => ['notes' => fake()->sentences(2, true)]);
    }

    /**
     * A client that has been archived, as if somebody had removed it from the working list.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['deleted_at' => CarbonImmutable::now()->subDays(3)]);
    }
}
