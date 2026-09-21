<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\ClientAttributeType;
use App\Enums\ClientStatus;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeValue;
use App\ValueObjects\ClientAttributeField;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clientPayload(array $overrides = []): array
{
    return [
        'name' => 'Northwind Studio',
        'email' => 'hello@northwind.test',
        'status' => ClientStatus::Active->value,
        ...$overrides,
    ];
}

describe('answering attributes on the client form', function (): void {
    it('stores an answer for every kind of attribute', function (): void {
        $text = ClientAttribute::factory()->create(['name' => 'Account owner', 'position' => 1]);
        $date = ClientAttribute::factory()->ofType(ClientAttributeType::Date)->create(['name' => 'Renewal', 'position' => 2]);
        $flag = ClientAttribute::factory()->ofType(ClientAttributeType::Boolean)->create(['name' => 'VIP', 'position' => 3]);
        $tier = ClientAttribute::factory()->select()->create(['name' => 'Tier', 'position' => 4]);

        actingAs(administrator())->post(route('clients.store'), clientPayload([
            'client_attributes' => [
                $text->getKey() => 'Ana Costa',
                $date->getKey() => '2026-03-01',
                $flag->getKey() => '1',
                $tier->getKey() => 'Gold',
            ],
        ]))->assertSessionHasNoErrors();

        $client = Client::query()->firstOrFail();
        $answers = $client->attributeValues->mapWithKeys(fn ($v) => [$v->client_attribute_id => $v->value]);

        expect($answers[$text->getKey()])->toBe('Ana Costa')
            ->and($answers[$date->getKey()])->toBe('2026-03-01')
            ->and($answers[$flag->getKey()])->toBeTrue()
            ->and($answers[$tier->getKey()])->toBe('Gold');
    });

    it('stores a repeater, including rows inside rows', function (): void {
        $contacts = ClientAttribute::factory()->nestedRepeater()->create(['name' => 'Contacts', 'position' => 1]);

        actingAs(administrator())->post(route('clients.store'), clientPayload([
            'client_attributes' => [
                $contacts->getKey() => [
                    ['name' => 'Ana', 'addresses' => [['city' => 'Porto'], ['city' => 'Lisboa']]],
                    ['name' => 'Rui', 'addresses' => []],
                ],
            ],
        ]))->assertSessionHasNoErrors();

        $value = ClientAttributeValue::query()->firstOrFail()->value;

        expect($value)->toHaveCount(2)
            ->and($value[0]['name'])->toBe('Ana')
            ->and($value[0]['addresses'])->toHaveCount(2)
            ->and($value[0]['addresses'][1]['city'])->toBe('Lisboa')
            ->and($value[1]['addresses'])->toBe([]);
    });

    it('drops a repeater row the browser added and nobody filled in', function (): void {
        $contacts = ClientAttribute::factory()->repeater()->create(['position' => 1]);

        actingAs(administrator())->post(route('clients.store'), clientPayload([
            'client_attributes' => [
                $contacts->getKey() => [
                    ['name' => 'Ana', 'extension' => '22'],
                    ['name' => '', 'extension' => ''],
                ],
            ],
        ]))->assertSessionHasNoErrors();

        expect(ClientAttributeValue::query()->firstOrFail()->value)->toHaveCount(1);
    });

    it('refuses to save a client without a required answer', function (): void {
        $attribute = ClientAttribute::factory()->required()->create(['name' => 'Account owner', 'position' => 1]);

        actingAs(administrator())
            ->post(route('clients.store'), clientPayload(['client_attributes' => [$attribute->getKey() => '']]))
            ->assertSessionHasErrors('client_attributes.'.$attribute->getKey());

        expect(Client::query()->count())->toBe(0);
    });

    it('refuses an answer that is not of the attribute type', function (): void {
        $attribute = ClientAttribute::factory()->ofType(ClientAttributeType::Number)->create(['position' => 1]);

        actingAs(administrator())
            ->post(route('clients.store'), clientPayload(['client_attributes' => [$attribute->getKey() => 'about twelve']]))
            ->assertSessionHasErrors('client_attributes.'.$attribute->getKey());
    });

    it('ignores an answer aimed at an attribute that is not on the form', function (): void {
        $retired = ClientAttribute::factory()->inactive()->create(['position' => 1]);

        actingAs(administrator())->post(route('clients.store'), clientPayload([
            'client_attributes' => [$retired->getKey() => 'Tampered', 9999 => 'Nonexistent'],
        ]))->assertSessionHasNoErrors();

        expect(ClientAttributeValue::query()->count())->toBe(0);
    });
});

describe('changing answers', function (): void {
    it('replaces an answer and clears one that was emptied', function (): void {
        $kept = ClientAttribute::factory()->create(['name' => 'Account owner', 'position' => 1]);
        $cleared = ClientAttribute::factory()->create(['name' => 'Region', 'position' => 2]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($kept, 'Ana Costa')->create();
        ClientAttributeValue::factory()->for($client)->of($cleared, 'North')->create();

        actingAs(administrator())->put(route('clients.update', $client), clientPayload([
            'email' => $client->email->value,
            'client_attributes' => [
                $kept->getKey() => 'Rui Silva',
                $cleared->getKey() => '',
            ],
        ]))->assertSessionHasNoErrors();

        // Clearing deletes the row rather than storing a null, so "in use by" stays honest.
        expect($client->fresh()->attributeValues)->toHaveCount(1)
            ->and($client->fresh()->attributeValues->first()->value)->toBe('Rui Silva');

        assertDatabaseMissing('client_attribute_values', ['client_attribute_id' => $cleared->getKey()]);
    });

    it('records the answers in the client audit entry, before and after', function (): void {
        $attribute = ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 1]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($attribute, 'Ana Costa')->create();

        actingAs(administrator())->put(route('clients.update', $client), clientPayload([
            'email' => $client->email->value,
            'client_attributes' => [$attribute->getKey() => 'Rui Silva'],
        ]))->assertSessionHasNoErrors();

        $audit = Audit::query()->where('action', AuditAction::ClientUpdated->value)->firstOrFail();

        expect($audit->old_values['attributes']['account_owner'])->toBe('Ana Costa')
            ->and($audit->new_values['attributes']['account_owner'])->toBe('Rui Silva');
    });
});

describe('an attribute that has been deactivated', function (): void {
    it('disappears from the form and the client page but keeps its answer', function (): void {
        $attribute = ClientAttribute::factory()->create(['name' => 'Account owner', 'position' => 1]);
        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($attribute, 'Ana Costa')->create();

        actingAs(administrator())->get(route('clients.show', $client))->assertOk()->assertSee('Ana Costa');

        $attribute->update(['is_active' => false]);

        actingAs(administrator())->get(route('clients.show', $client))->assertOk()->assertDontSee('Ana Costa');
        actingAs(administrator())->get(route('clients.edit', $client))->assertOk()->assertDontSee('Account owner');

        // Saving the client while it is hidden must not quietly delete the answer.
        actingAs(administrator())->put(route('clients.update', $client), clientPayload([
            'email' => $client->email->value,
        ]))->assertSessionHasNoErrors();

        $attribute->update(['is_active' => true]);

        expect($client->fresh()->attributeValues->first()->value)->toBe('Ana Costa');
        actingAs(administrator())->get(route('clients.show', $client))->assertOk()->assertSee('Ana Costa');
    });
});

describe('the client page', function (): void {
    it('reads a repeater as rows rather than as one line of text', function (): void {
        $contacts = ClientAttribute::factory()->nestedRepeater()->create(['name' => 'Contacts', 'position' => 1]);
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'position' => 2]);

        $client = Client::factory()->create();

        // Two answers, because Eloquent only guards a result set of more than one row.
        ClientAttributeValue::factory()->for($client)->of($contacts, [
            ['name' => 'Ana', 'addresses' => [['city' => 'Porto'], ['city' => 'Lisboa']]],
        ])->create();
        ClientAttributeValue::factory()->for($client)->of($owner, 'Rui Silva')->create();

        actingAs(administrator())
            ->get(route('clients.show', $client))
            ->assertOk()
            // The one-line reading is for a CSV cell and an email; a page has the room.
            ->assertDontSee('Name: Ana')
            ->assertSee('Addresses')
            ->assertSee('Ana')
            ->assertSee('Porto')
            ->assertSee('Lisboa')
            ->assertSee('Rui Silva');
    });

    it('reads a row of one unnamed field as a plain list of values', function (): void {
        $domains = ClientAttribute::factory()->repeater([
            new ClientAttributeField('field_1', '', ClientAttributeType::Url),
        ])->create(['name' => 'Domains', 'position' => 1]);
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'position' => 2]);

        $client = Client::factory()->create();

        ClientAttributeValue::factory()->for($client)->of($domains, [
            ['field_1' => 'https://dcruz.com'],
            ['field_1' => 'https://dcruz.pt'],
        ])->create();
        ClientAttributeValue::factory()->for($client)->of($owner, 'Rui Silva')->create();

        actingAs(administrator())
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('https://dcruz.com')
            ->assertSee('https://dcruz.pt');
    });

    it('leaves out an attribute whose rows were all removed', function (): void {
        $contacts = ClientAttribute::factory()->repeater()->create(['name' => 'Contacts', 'position' => 1]);
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'position' => 2]);

        $client = Client::factory()->create();

        ClientAttributeValue::factory()->for($client)->of($contacts, [])->create();
        ClientAttributeValue::factory()->for($client)->of($owner, 'Rui Silva')->create();

        actingAs(administrator())
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertDontSee('Contacts')
            ->assertSee('Rui Silva');
    });
});
