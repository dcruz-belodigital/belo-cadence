<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAttributeValue>
 */
final class ClientAttributeValueFactory extends Factory
{
    protected $model = ClientAttributeValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'client_attribute_id' => ClientAttribute::factory(),
            'value' => fake()->word(),
        ];
    }

    public function of(ClientAttribute $attribute, mixed $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'client_attribute_id' => $attribute->getKey(),
            'value' => $value,
        ]);
    }
}
