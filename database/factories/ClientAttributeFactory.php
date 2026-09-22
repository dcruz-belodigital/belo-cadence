<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientAttributeType;
use App\Models\ClientAttribute;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClientAttribute>
 */
final class ClientAttributeFactory extends Factory
{
    protected $model = ClientAttribute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // A closure so an overridden name still decides the identifier, the way the
            // form derives it.
            'key' => fn (array $attributes): string => Str::slug((string) $attributes['name'], '_'),
            'name' => Str::title(fake()->unique()->words(2, true)),
            'type' => ClientAttributeType::Text,
            'hint' => null,
            'options' => new ClientAttributeChoices,
            'fields' => [],
            'is_required' => false,
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function ofType(ClientAttributeType $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type]);
    }

    /**
     * @param  list<string>  $choices
     */
    public function select(array $choices = ['Gold', 'Silver', 'Bronze']): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ClientAttributeType::Select,
            'options' => ClientAttributeChoices::fromArray($choices),
        ]);
    }

    /**
     * A repeater of a string and a number, which is the shape the feature was asked for.
     *
     * @param  list<ClientAttributeField>|null  $fields
     */
    public function repeater(?array $fields = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ClientAttributeType::Repeater,
            'fields' => $fields ?? [
                new ClientAttributeField('name', 'Name', ClientAttributeType::Text, isRequired: true),
                new ClientAttributeField('extension', 'Extension', ClientAttributeType::Number),
            ],
        ]);
    }

    /**
     * A repeater whose rows themselves contain a repeater.
     */
    public function nestedRepeater(): static
    {
        return $this->repeater([
            new ClientAttributeField('name', 'Name', ClientAttributeType::Text),
            new ClientAttributeField('addresses', 'Addresses', ClientAttributeType::Repeater, fields: [
                new ClientAttributeField('city', 'City', ClientAttributeType::Text),
            ]),
        ]);
    }

    /**
     * A repeater whose rows each hold a file, which is what an email attaches one of per
     * row.
     */
    public function repeaterOfFiles(): static
    {
        return $this->repeater([
            new ClientAttributeField('label', 'Label', ClientAttributeType::Text),
            new ClientAttributeField('document', 'Document', ClientAttributeType::File),
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes): array => ['is_required' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    public function at(int $position): static
    {
        return $this->state(fn (array $attributes): array => ['position' => $position]);
    }
}
