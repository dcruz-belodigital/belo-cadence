<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\ClientAttributeType;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeValue;
use App\ValueObjects\ClientAttributeChoices;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

describe('listing attributes', function (): void {
    it('shows each attribute with how many clients have answered it', function (): void {
        $attribute = ClientAttribute::factory()->create(['name' => 'Renewal date']);
        ClientAttribute::factory()->create(['name' => 'Account owner']);

        ClientAttributeValue::factory()->for(Client::factory())->of($attribute, '2026-03-01')->create();
        ClientAttributeValue::factory()->for(Client::factory())->of($attribute, '2026-04-01')->create();

        $page = actingAs(administrator())
            ->get(route('admin.client-attributes.index'))
            ->assertOk()
            ->assertSee('Renewal date')
            ->assertSee('Account owner');

        expect($page->getContent())->toContain('renewal_date');
    });

    it('searches by name and by identifier', function (): void {
        ClientAttribute::factory()->create(['name' => 'Renewal date', 'key' => 'renewal_date']);
        ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner']);

        actingAs(administrator())
            ->get(route('admin.client-attributes.index', ['search' => 'renewal']))
            ->assertOk()
            ->assertSee('Renewal date')
            ->assertDontSee('Account owner');
    });

    it('filters by type and by state', function (): void {
        ClientAttribute::factory()->select()->create(['name' => 'Tier']);
        ClientAttribute::factory()->inactive()->create(['name' => 'Retired field']);

        actingAs(administrator())
            ->get(route('admin.client-attributes.index', ['type' => ClientAttributeType::Select->value]))
            ->assertOk()
            ->assertSee('Tier')
            ->assertDontSee('Retired field');

        actingAs(administrator())
            ->get(route('admin.client-attributes.index', ['state' => 'inactive']))
            ->assertOk()
            ->assertSee('Retired field')
            ->assertDontSee('Tier');
    });

    it('orders by position, and that is what every other surface reads', function (): void {
        ClientAttribute::factory()->at(2)->create(['name' => 'Second']);
        ClientAttribute::factory()->at(1)->create(['name' => 'First']);

        expect(ClientAttribute::query()->active()->pluck('name')->all())->toBe(['First', 'Second']);
    });
});

describe('creating an attribute', function (): void {
    it('creates one, derives its identifier from the name, and records it', function (): void {
        actingAs(administrator())->post(route('admin.client-attributes.store'), [
            'name' => 'Renewal date',
            'type' => ClientAttributeType::Date->value,
            'hint' => 'When the retainer is next up.',
            'position' => 3,
            'is_required' => '1',
            'is_active' => '1',
        ])->assertRedirect();

        $attribute = ClientAttribute::query()->firstOrFail();

        expect($attribute->key)->toBe('renewal_date')
            ->and($attribute->type)->toBe(ClientAttributeType::Date)
            ->and($attribute->is_required)->toBeTrue()
            ->and($attribute->position)->toBe(3);

        assertDatabaseHas('audits', [
            'action' => AuditAction::ClientAttributeCreated->value,
            'auditable_type' => 'client_attribute',
            'auditable_id' => $attribute->getKey(),
        ]);
    });

    it('creates a repeater with the fields that make up each row', function (): void {
        actingAs(administrator())->post(route('admin.client-attributes.store'), [
            'name' => 'Contacts',
            'type' => ClientAttributeType::Repeater->value,
            'position' => 0,
            'fields' => [
                ['name' => 'Name', 'type' => ClientAttributeType::Text->value, 'is_required' => '1'],
                ['name' => 'Extension', 'type' => ClientAttributeType::Number->value],
            ],
        ])->assertRedirect();

        $fields = ClientAttribute::query()->firstOrFail()->fields;

        expect($fields)->toHaveCount(2)
            ->and($fields[0]->key)->toBe('name')
            ->and($fields[0]->isRequired)->toBeTrue()
            ->and($fields[1]->type)->toBe(ClientAttributeType::Number);
    });

    it('creates a repeater whose rows hold repeating rows of their own', function (): void {
        actingAs(administrator())->post(route('admin.client-attributes.store'), [
            'name' => 'Contacts',
            'type' => ClientAttributeType::Repeater->value,
            'position' => 0,
            'fields' => [
                [
                    'name' => 'Name',
                    'type' => ClientAttributeType::Text->value,
                ],
                [
                    'name' => 'Addresses',
                    'type' => ClientAttributeType::Repeater->value,
                    'fields' => [
                        ['name' => 'City', 'type' => ClientAttributeType::Text->value],
                    ],
                ],
            ],
        ])->assertSessionHasNoErrors();

        $fields = ClientAttribute::query()->firstOrFail()->fields;

        expect($fields[1]->type)->toBe(ClientAttributeType::Repeater)
            ->and($fields[1]->fields)->toHaveCount(1)
            ->and($fields[1]->fields[0]->key)->toBe('city');
    });

    it('drops a row somebody added and never filled in', function (): void {
        actingAs(administrator())->post(route('admin.client-attributes.store'), [
            'name' => 'Contacts',
            'type' => ClientAttributeType::Repeater->value,
            'position' => 0,
            'fields' => [
                ['name' => 'Name', 'type' => ClientAttributeType::Text->value],
                ['name' => '', 'type' => ClientAttributeType::Text->value],
            ],
        ])->assertSessionHasNoErrors();

        expect(ClientAttribute::query()->firstOrFail()->fields)->toHaveCount(1);
    });

    it('refuses a definition that would not work', function (array $payload, string $field): void {
        ClientAttribute::factory()->create(['name' => 'Taken', 'key' => 'taken']);

        actingAs(administrator())
            ->post(route('admin.client-attributes.store'), [
                'name' => 'Something',
                'type' => ClientAttributeType::Text->value,
                'position' => 0,
                ...$payload,
            ])
            ->assertSessionHasErrors($field);
    })->with([
        'no name' => [['name' => ''], 'name'],
        'a name already taken' => [['name' => 'Taken'], 'name'],
        // "Taken" and "taken!" both reduce to the identifier `taken`.
        'a name that reduces to one already taken' => [['name' => 'taken!'], 'name'],
        'a name reserved by a built-in field' => [['name' => 'Email'], 'name'],
        'no type' => [['type' => ''], 'type'],
        'an unknown type' => [['type' => 'colour'], 'type'],
        'no position' => [['position' => ''], 'position'],
        'a list with nothing to choose' => [['type' => 'select', 'options' => '  '], 'options'],
        'repeating rows made of nothing' => [['type' => 'repeater', 'fields' => []], 'fields'],
    ]);

    it('refuses rows nested deeper than the editor can draw', function (): void {
        $deepest = ['name' => 'Too deep', 'type' => ClientAttributeType::Text->value];

        // One level past MAX_DEPTH, which only a hand-made payload can reach.
        for ($level = 0; $level < ClientAttribute::MAX_DEPTH; $level++) {
            $deepest = ['name' => 'Level', 'type' => ClientAttributeType::Repeater->value, 'fields' => [$deepest]];
        }

        actingAs(administrator())
            ->post(route('admin.client-attributes.store'), [
                'name' => 'Contacts',
                'type' => ClientAttributeType::Repeater->value,
                'position' => 0,
                'fields' => [$deepest],
            ])
            ->assertSessionHasErrors('fields');
    });

    it('refuses somebody without permission to create one', function (): void {
        actingAs(administratorWithout([PermissionName::ClientAttributesCreate]))
            ->post(route('admin.client-attributes.store'), [
                'name' => 'Renewal date',
                'type' => ClientAttributeType::Date->value,
                'position' => 0,
            ])
            ->assertForbidden();

        expect(ClientAttribute::query()->count())->toBe(0);
    });
});

describe('editing an attribute', function (): void {
    it('changes what can change and leaves the identifier and type alone', function (): void {
        $attribute = ClientAttribute::factory()->create([
            'name' => 'Renewal date',
            'key' => 'renewal_date',
            'type' => ClientAttributeType::Date,
            'position' => 0,
        ]);

        actingAs(administrator())->put(route('admin.client-attributes.update', $attribute), [
            'name' => 'Next renewal',
            // Both are ignored: the form does not offer them and the action never writes them.
            'key' => 'something_else',
            'type' => ClientAttributeType::Number->value,
            'hint' => 'Updated hint',
            'position' => 5,
            'is_active' => '0',
        ])->assertRedirect();

        $attribute->refresh();

        expect($attribute->name)->toBe('Next renewal')
            ->and($attribute->key)->toBe('renewal_date')
            ->and($attribute->type)->toBe(ClientAttributeType::Date)
            ->and($attribute->hint)->toBe('Updated hint')
            ->and($attribute->position)->toBe(5)
            ->and($attribute->is_active)->toBeFalse();

        assertDatabaseHas('audits', [
            'action' => AuditAction::ClientAttributeUpdated->value,
            'auditable_id' => $attribute->getKey(),
        ]);
    });

    it('retires an option somebody removed instead of losing it', function (): void {
        $attribute = ClientAttribute::factory()->select(['Gold', 'Silver', 'Bronze'])->create(['position' => 0]);

        actingAs(administrator())->put(route('admin.client-attributes.update', $attribute), [
            'name' => $attribute->name,
            'position' => 0,
            'is_active' => '1',
            'options' => "Gold\nSilver",
        ])->assertRedirect();

        $options = $attribute->refresh()->options;

        // A client already on Bronze keeps a valid answer, and stops being offered it.
        expect($options->activeValues())->toBe(['Gold', 'Silver'])
            ->and($options->values())->toContain('Bronze')
            ->and($options->isRetired('Bronze'))->toBeTrue();
    });

    it('keeps the answers already recorded when a field of a row is renamed', function (): void {
        $attribute = ClientAttribute::factory()->repeater()->create(['position' => 0]);

        actingAs(administrator())->put(route('admin.client-attributes.update', $attribute), [
            'name' => $attribute->name,
            'position' => 0,
            'is_active' => '1',
            'fields' => [
                // The key travels in a hidden field, so a rename is not a new field.
                ['key' => 'name', 'name' => 'Full name', 'type' => ClientAttributeType::Text->value],
                ['key' => 'extension', 'name' => 'Extension', 'type' => ClientAttributeType::Number->value],
            ],
        ])->assertRedirect();

        $fields = $attribute->refresh()->fields;

        expect($fields[0]->key)->toBe('name')
            ->and($fields[0]->name)->toBe('Full name');
    });

    it('refuses somebody without permission to change one', function (): void {
        $attribute = ClientAttribute::factory()->create(['name' => 'Renewal date', 'position' => 0]);

        actingAs(administratorWithout([PermissionName::ClientAttributesUpdate]))
            ->put(route('admin.client-attributes.update', $attribute), [
                'name' => 'Changed',
                'position' => 0,
            ])
            ->assertForbidden();

        expect($attribute->refresh()->name)->toBe('Renewal date');
    });
});

describe('deleting an attribute', function (): void {
    it('takes every answer recorded against it, and says how many there were', function (): void {
        $attribute = ClientAttribute::factory()->create(['name' => 'Renewal date']);

        ClientAttributeValue::factory()->for(Client::factory())->of($attribute, '2026-03-01')->create();
        ClientAttributeValue::factory()->for(Client::factory())->of($attribute, '2026-04-01')->create();

        actingAs(administrator())
            ->delete(route('admin.client-attributes.destroy', $attribute))
            ->assertRedirect(route('admin.client-attributes.index'));

        assertDatabaseMissing('client_attributes', ['id' => $attribute->getKey()]);
        assertDatabaseMissing('client_attribute_values', ['client_attribute_id' => $attribute->getKey()]);

        $audit = Audit::query()
            ->where('action', AuditAction::ClientAttributeDeleted->value)
            ->firstOrFail();

        expect($audit->metadata['deleted_values'])->toBe(2)
            ->and($audit->old_values['key'])->toBe('renewal_date');
    });

    it('refuses somebody without permission to delete one', function (): void {
        $attribute = ClientAttribute::factory()->create();

        actingAs(administratorWithout([PermissionName::ClientAttributesDelete]))
            ->delete(route('admin.client-attributes.destroy', $attribute))
            ->assertForbidden();

        assertDatabaseHas('client_attributes', ['id' => $attribute->getKey()]);
    });
});

describe('the pages themselves', function (): void {
    it('renders the form for every type an attribute can be', function (ClientAttributeType $type): void {
        $attribute = ClientAttribute::factory()->ofType($type)->create();

        if ($type->usesChoices()) {
            $attribute->update(['options' => ClientAttributeChoices::fromArray(['Gold'])]);
        }

        actingAs(administrator())->get(route('admin.client-attributes.edit', $attribute))->assertOk();
        actingAs(administrator())->get(route('admin.client-attributes.show', $attribute))->assertOk();
    })->with(ClientAttributeType::cases());
});
