<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClientAttributeType;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;
use Illuminate\Database\Seeder;

/**
 * Clearly fictional client attributes, covering every type the interface can draw —
 * including a repeater whose rows themselves repeat, and one that has been retired so
 * the "hidden everywhere, kept anyway" behaviour is visible in a demonstration.
 */
final class DemoClientAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->attribute('account_owner', 'Account owner', ClientAttributeType::Text, 1, [
            'hint' => 'Who looks after this client day to day.',
        ]);

        $renewal = $this->attribute('renewal_date', 'Renewal date', ClientAttributeType::Date, 2);

        $tier = $this->attribute('tier', 'Tier', ClientAttributeType::Select, 3, [
            'options' => ClientAttributeChoices::fromArray(['Gold', 'Silver', 'Bronze']),
        ]);

        $seats = $this->attribute('seats', 'Seats', ClientAttributeType::Number, 4);

        $vip = $this->attribute('priority_support', 'Priority support', ClientAttributeType::Boolean, 5);

        $contacts = $this->attribute('contacts', 'Contacts', ClientAttributeType::Repeater, 6, [
            'hint' => 'Everybody worth calling, and where they are.',
            'fields' => [
                new ClientAttributeField('name', 'Name', ClientAttributeType::Text, isRequired: true),
                new ClientAttributeField('email', 'Email', ClientAttributeType::Email),
                new ClientAttributeField('offices', 'Offices', ClientAttributeType::Repeater, fields: [
                    new ClientAttributeField('city', 'City', ClientAttributeType::Text),
                    new ClientAttributeField('extension', 'Extension', ClientAttributeType::Number),
                ]),
            ],
        ]);

        // Retired rather than deleted: its answers are still there, and still hidden.
        $this->attribute('legacy_reference', 'Legacy reference', ClientAttributeType::Text, 7, [
            'is_active' => false,
        ]);

        $northwind = Client::query()->where('email', 'hello@northwind.test')->first();
        $harbour = Client::query()->where('email', 'accounts@harbourpine.test')->first();

        if ($northwind instanceof Client) {
            $this->answer($northwind, $owner, 'Ana Costa');
            $this->answer($northwind, $renewal, '2027-01-15');
            $this->answer($northwind, $tier, 'Gold');
            $this->answer($northwind, $seats, 24);
            $this->answer($northwind, $vip, true);
            $this->answer($northwind, $contacts, [
                [
                    'name' => 'Ana Costa',
                    'email' => 'ana@northwind.test',
                    'offices' => [
                        ['city' => 'Porto', 'extension' => 210],
                        ['city' => 'Lisboa', 'extension' => 211],
                    ],
                ],
                ['name' => 'Rui Silva', 'email' => 'rui@northwind.test', 'offices' => []],
            ]);
        }

        if ($harbour instanceof Client) {
            $this->answer($harbour, $owner, 'Rui Silva');
            $this->answer($harbour, $tier, 'Silver');
            $this->answer($harbour, $seats, 6);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function attribute(string $key, string $name, ClientAttributeType $type, int $position, array $overrides = []): ClientAttribute
    {
        return ClientAttribute::query()->updateOrCreate(
            ['key' => $key],
            [
                'name' => $name,
                'type' => $type,
                'position' => $position,
                'is_active' => true,
                'is_required' => false,
                ...$overrides,
            ],
        );
    }

    private function answer(Client $client, ClientAttribute $attribute, mixed $value): void
    {
        $client->attributeValues()->updateOrCreate(
            ['client_attribute_id' => $attribute->getKey()],
            ['value' => $value],
        );
    }
}
