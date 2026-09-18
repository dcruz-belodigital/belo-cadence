<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\ClientAttributeType;
use App\Enums\ClientStatus;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeValue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    // Uploaded files are written to a throwaway disk for the duration of the test.
    Storage::fake('local');
});

function csvUpload(string $contents, string $name = 'clients.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $contents);
}

/**
 * Step one: hand the file over and land on the matching step.
 */
function uploadClients(string $csv, ?User $as = null): TestResponse
{
    return actingAs($as ?? administrator())
        ->post(route('clients.import.store'), ['file' => csvUpload($csv)]);
}

/**
 * Step two: confirm the matching and import.
 *
 * @param  array<string, string|null>  $mapping
 */
function importClients(array $mapping, ?User $as = null): TestResponse
{
    return actingAs($as ?? administrator())
        ->post(route('clients.import.run'), ['mapping' => $mapping]);
}

/**
 * A file that already uses this application's own column names maps to itself.
 *
 * @return array<string, string>
 */
function identityMapping(string $csv): array
{
    $headings = str_getcsv((string) strtok(trim($csv), "\n"), escape: '');

    return array_combine($headings, $headings);
}

describe('the import template', function (): void {
    it('can be downloaded and shows the expected columns', function (): void {
        $response = actingAs(administrator())
            ->get(route('clients.import.template'))
            ->assertOk();

        $content = ltrim($response->streamedContent(), "\xEF\xBB\xBF");

        expect($content)->toContain('id,name,email,status,notes')
            ->and($content)->toContain('client@example.com');
    });

    it('lists every column it understands on the upload page', function (): void {
        actingAs(administrator())
            ->get(route('clients.import.create'))
            ->assertOk()
            ->assertSee(__('imports.available_columns'))
            ->assertSee(__('clients.fields.email'))
            ->assertSee(__('imports.columns.required'))
            ->assertSee('client@example.com');
    });
});

describe('matching the columns', function (): void {
    it('takes the upload to the matching step', function (): void {
        uploadClients("name,email,status\nNorthwind Studio,hello@northwind.test,active\n")
            ->assertRedirect(route('clients.import.mapping'));

        expect(Client::query()->count())->toBe(0);
    });

    it('suggests a match for every column it recognises', function (): void {
        uploadClients("name,email,status\nNorthwind Studio,hello@northwind.test,active\n");

        $page = actingAs(administrator())->get(route('clients.import.mapping'))->assertOk();

        expect($page->getContent())
            ->toContain(__('imports.mapping.title'))
            // The three headings it recognised are selected.
            ->toMatch('/<option value="name" selected/')
            ->toMatch('/<option value="email" selected/')
            ->toMatch('/<option value="status" selected/');
    });

    it('matches headings written for people, not for machines', function (): void {
        uploadClients("Client name,Email address,Status\nNorthwind Studio,hello@northwind.test,active\n");

        actingAs(administrator())
            ->get(route('clients.import.mapping'))
            ->assertOk()
            ->assertSee('Client name')
            ->assertSee('Email address');

        importClients([
            'name' => 'Client name',
            'email' => 'Email address',
            'status' => 'Status',
        ])->assertRedirect(route('clients.index'));

        expect(Client::query()->firstOrFail()->name)->toBe('Northwind Studio');
    });

    it('shows the first rows of the file so the reader can check them', function (): void {
        $csv = "name,email,status\nNorthwind Studio,hello@northwind.test,active\nHarbour & Pine,accounts@harbour.test,inactive\n";

        uploadClients($csv);

        actingAs(administrator())
            ->get(route('clients.import.mapping'))
            ->assertOk()
            ->assertSee(__('imports.mapping.preview'))
            ->assertSee('Northwind Studio')
            ->assertSee('accounts@harbour.test')
            ->assertSee('clients.csv');
    });

    it('imports a column the reader chose to leave out as absent', function (): void {
        uploadClients("name,email,status,notes\nNorthwind Studio,hello@northwind.test,active,Ignore me\n");

        importClients([
            'name' => 'name',
            'email' => 'email',
            'status' => 'status',
            'notes' => null,
        ])->assertRedirect();

        expect(Client::query()->firstOrFail()->notes)->toBeNull();
    });

    it('refuses to leave a required column unmatched', function (): void {
        uploadClients("name,email,status\nNorthwind Studio,hello@northwind.test,active\n");

        importClients(['name' => 'name', 'email' => null, 'status' => 'status'])
            ->assertSessionHasErrors('mapping');

        expect(Client::query()->count())->toBe(0);
    });

    it('refuses to use one heading for two columns', function (): void {
        uploadClients("name,email,status\nNorthwind Studio,hello@northwind.test,active\n");

        importClients(['name' => 'name', 'email' => 'name', 'status' => 'status'])
            ->assertSessionHasErrors('mapping');

        expect(Client::query()->count())->toBe(0);
    });

    it('refuses a heading that is not in the file', function (): void {
        uploadClients("name,email,status\nNorthwind Studio,hello@northwind.test,active\n");

        importClients(['name' => 'name', 'email' => 'email', 'status' => 'status', 'notes' => 'invented'])
            ->assertSessionHasErrors('mapping.notes');
    });

    it('sends the reader back to the upload step when there is no file waiting', function (): void {
        actingAs(administrator())
            ->get(route('clients.import.mapping'))
            ->assertRedirect(route('clients.import.create'))
            ->assertSessionHas('error', __('imports.errors.expired'));
    });
});

describe('a valid file', function (): void {
    it('creates the clients it contains', function (): void {
        $csv = <<<'CSV'
        id,name,email,status,notes
        ,Northwind Studio,hello@northwind.test,active,Prefers Mondays
        ,Harbour & Pine,accounts@harbour.test,inactive,
        CSV;

        uploadClients($csv);

        importClients(identityMapping($csv))
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success');

        expect(Client::query()->count())->toBe(2);

        $northwind = Client::query()->where('email', 'hello@northwind.test')->firstOrFail();

        expect($northwind->name)->toBe('Northwind Studio')
            ->and($northwind->status)->toBe(ClientStatus::Active)
            ->and($northwind->notes)->toBe('Prefers Mondays');

        expect(Client::query()->where('email', 'accounts@harbour.test')->firstOrFail())
            ->status->toBe(ClientStatus::Inactive)
            ->notes->toBeNull();
    });

    it('routes each row through the ordinary create action, so audit entries appear', function (): void {
        $csv = "name,email,status\nNorthwind Studio,hello@northwind.test,active\n";

        uploadClients($csv);
        importClients(identityMapping($csv));

        assertDatabaseHas('audits', ['action' => AuditAction::ClientCreated->value]);
        assertDatabaseHas('audits', ['action' => AuditAction::ClientsImported->value]);
    });

    it('updates an existing client when the row carries its identifier', function (): void {
        $client = Client::factory()->create(['name' => 'Old Name', 'email' => 'hello@northwind.test']);

        $csv = "id,name,email,status\n{$client->getKey()},New Name,hello@northwind.test,inactive\n";

        uploadClients($csv);

        importClients(identityMapping($csv))->assertSessionHas('success');

        expect(Client::query()->count())->toBe(1)
            ->and($client->fresh()->name)->toBe('New Name')
            ->and($client->fresh()->status)->toBe(ClientStatus::Inactive);

        assertDatabaseHas('audits', ['action' => AuditAction::ClientUpdated->value]);
    });

    it('normalises addresses the same way the form does', function (): void {
        $csv = "name,email,status\nNorthwind Studio,  Hello@Northwind.TEST ,active\n";

        uploadClients($csv);
        importClients(identityMapping($csv));

        expect(Client::query()->firstOrFail()->email->value)->toBe('hello@northwind.test');
    });

    it('reports what it did', function (): void {
        $existing = Client::factory()->create(['email' => 'hello@northwind.test']);

        $csv = <<<CSV
        id,name,email,status
        {$existing->getKey()},Northwind Studio,hello@northwind.test,active
        ,Harbour & Pine,accounts@harbour.test,active
        CSV;

        uploadClients($csv);

        importClients(identityMapping($csv))
            ->assertSessionHas('success', __('imports.flash.clients', ['created' => 1, 'updated' => 1]));
    });

    it('forgets the file once the import is done', function (): void {
        $csv = "name,email,status\nNorthwind Studio,hello@northwind.test,active\n";

        uploadClients($csv);

        expect(Storage::disk('local')->files('imports'))->toHaveCount(1);

        importClients(identityMapping($csv));

        expect(Storage::disk('local')->files('imports'))->toBe([]);

        actingAs(administrator())
            ->get(route('clients.import.mapping'))
            ->assertRedirect(route('clients.import.create'));
    });

    it('replaces the waiting file when another one is uploaded', function (): void {
        uploadClients("name,email,status\nFirst,first@example.test,active\n");
        uploadClients("name,email,status\nSecond,second@example.test,active\n");

        expect(Storage::disk('local')->files('imports'))->toHaveCount(1);

        actingAs(administrator())
            ->get(route('clients.import.mapping'))
            ->assertOk()
            ->assertSee('Second')
            ->assertDontSee('First');
    });
});

describe('a file that cannot be imported', function (): void {
    it('refuses a file with no header row', function (): void {
        uploadClients("\n")->assertSessionHasErrors('file');
    });

    it('refuses something that is not a csv file', function (): void {
        actingAs(administrator())
            ->post(route('clients.import.store'), ['file' => UploadedFile::fake()->image('clients.png')])
            ->assertSessionHasErrors('file');
    });

    it('refuses a file with no rows before it is even stored', function (): void {
        uploadClients("name,email,status\n")->assertSessionHasErrors('file');

        expect(Storage::disk('local')->files('imports'))->toBe([]);
    });

    it('reports the line number of an invalid row', function (): void {
        $csv = <<<'CSV'
        name,email,status
        Northwind Studio,hello@northwind.test,active
        Harbour & Pine,not-an-email,active
        CSV;

        uploadClients($csv);

        importClients(identityMapping($csv))->assertSessionHasErrors('file');

        expect(implode(' ', session('errors')->get('file')))->toContain('Line 3');
    });

    it('rejects an unknown status', function (): void {
        $csv = "name,email,status\nNorthwind Studio,hello@northwind.test,paused\n";

        uploadClients($csv);

        importClients(identityMapping($csv))->assertSessionHasErrors('file');

        expect(Client::query()->count())->toBe(0);
    });

    it('rejects an address another client already uses', function (): void {
        Client::factory()->create(['email' => 'hello@northwind.test']);

        $csv = "name,email,status\nAnother Northwind,hello@northwind.test,active\n";

        uploadClients($csv);

        importClients(identityMapping($csv))->assertSessionHasErrors('file');

        expect(Client::query()->count())->toBe(1);
    });

    it('rejects the same address appearing twice in one file', function (): void {
        $csv = <<<'CSV'
        name,email,status
        Northwind Studio,hello@northwind.test,active
        Northwind Again,hello@northwind.test,active
        CSV;

        uploadClients($csv);

        importClients(identityMapping($csv))->assertSessionHasErrors('file');

        expect(Client::query()->count())->toBe(0);
    });

    it('imports nothing at all when a single row is wrong', function (): void {
        $csv = <<<'CSV'
        name,email,status
        Northwind Studio,hello@northwind.test,active
        Harbour & Pine,accounts@harbour.test,active
        Broken Row,also-not-an-email,active
        CSV;

        uploadClients($csv);

        importClients(identityMapping($csv))->assertSessionHasErrors('file');

        expect(Client::query()->count())->toBe(0)
            ->and(Audit::query()->count())->toBe(0);
    });

    it('keeps the file waiting so the reader can correct the matching', function (): void {
        $csv = "name,email,status\nNorthwind Studio,not-an-email,active\n";

        uploadClients($csv);

        importClients(identityMapping($csv))->assertSessionHasErrors('file');

        actingAs(administrator())
            ->get(route('clients.import.mapping'))
            ->assertOk();
    });
});

describe('authorisation', function (): void {
    it('refuses every step to a user without the import permission', function (): void {
        $user = administratorWithout([PermissionName::ClientsImport]);

        actingAs($user)->get(route('clients.import.create'))->assertForbidden();
        actingAs($user)->post(route('clients.import.store'), ['file' => csvUpload("name,email,status\n")])->assertForbidden();
        actingAs($user)->get(route('clients.import.mapping'))->assertForbidden();
        actingAs($user)->post(route('clients.import.run'), ['mapping' => []])->assertForbidden();
    });

    it('refuses a user who may import but not create clients', function (): void {
        $user = administratorWithout([PermissionName::ClientsCreate]);

        actingAs($user)
            ->post(route('clients.import.store'), ['file' => csvUpload("name,email,status\nA,a@b.test,active\n")])
            ->assertForbidden();

        expect(Client::query()->count())->toBe(0);
    });
});

describe('custom attributes in the import', function (): void {
    it('offers a column for every active attribute, and none for a retired one', function (): void {
        ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 1]);
        ClientAttribute::factory()->inactive()->create(['name' => 'Retired field', 'key' => 'retired_field', 'position' => 2]);

        $content = ltrim(actingAs(administrator())
            ->get(route('clients.import.template'))
            ->assertOk()
            ->streamedContent(), "\xEF\xBB\xBF");

        expect($content)->toContain('attribute_account_owner')
            ->and($content)->not->toContain('attribute_retired_field');
    });

    it('records an answer from a mapped column', function (): void {
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 1]);

        $csv = "name,email,status,attribute_account_owner\nNorthwind,hello@northwind.test,active,Ana Costa\n";

        uploadClients($csv)->assertRedirect(route('clients.import.mapping'));
        importClients(identityMapping($csv))->assertSessionHasNoErrors();

        expect(Client::query()->firstOrFail()->attributeValues->first()->value)->toBe('Ana Costa')
            ->and(ClientAttributeValue::query()->where('client_attribute_id', $owner->getKey())->count())->toBe(1);
    });

    it('leaves an answer alone when its column is not in the file', function (): void {
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 1]);

        $client = Client::factory()->create(['name' => 'Northwind', 'email' => 'hello@northwind.test']);
        ClientAttributeValue::factory()->for($client)->of($owner, 'Ana Costa')->create();

        // A file that only means to correct the name must not wipe what it never carried.
        $csv = "id,name,email,status\n{$client->getKey()},Northwind Studio,hello@northwind.test,active\n";

        uploadClients($csv)->assertRedirect(route('clients.import.mapping'));
        importClients(identityMapping($csv))->assertSessionHasNoErrors();

        expect($client->fresh()->name)->toBe('Northwind Studio')
            ->and($client->fresh()->attributeValues->first()->value)->toBe('Ana Costa');
    });

    it('clears an answer when its column is mapped and the cell is empty', function (): void {
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 1]);

        $client = Client::factory()->create(['name' => 'Northwind', 'email' => 'hello@northwind.test']);
        ClientAttributeValue::factory()->for($client)->of($owner, 'Ana Costa')->create();

        $csv = "id,name,email,status,attribute_account_owner\n{$client->getKey()},Northwind,hello@northwind.test,active,\n";

        uploadClients($csv)->assertRedirect(route('clients.import.mapping'));
        importClients(identityMapping($csv))->assertSessionHasNoErrors();

        expect($client->fresh()->attributeValues)->toHaveCount(0);
    });

    it('refuses a row whose mapped cell is empty for a required attribute', function (): void {
        ClientAttribute::factory()->required()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 1]);

        $csv = "name,email,status,attribute_account_owner\nNorthwind,hello@northwind.test,active,\n";

        uploadClients($csv)->assertRedirect(route('clients.import.mapping'));
        importClients(identityMapping($csv))->assertSessionHasErrors('file');

        expect(Client::query()->count())->toBe(0);
    });

    it('reads a repeater from one json cell, nesting included', function (): void {
        ClientAttribute::factory()->nestedRepeater()->create(['name' => 'Contacts', 'key' => 'contacts', 'position' => 1]);

        $json = '"[{""name"":""Ana"",""addresses"":[{""city"":""Porto""}]}]"';
        $csv = "name,email,status,attribute_contacts\nNorthwind,hello@northwind.test,active,{$json}\n";

        uploadClients($csv)->assertRedirect(route('clients.import.mapping'));
        importClients(identityMapping($csv))->assertSessionHasNoErrors();

        $value = ClientAttributeValue::query()->firstOrFail()->value;

        expect($value[0]['name'])->toBe('Ana')
            ->and($value[0]['addresses'][0]['city'])->toBe('Porto');
    });

    it('reports a repeater cell that is not json against its own line', function (): void {
        ClientAttribute::factory()->repeater()->create(['name' => 'Contacts', 'key' => 'contacts', 'position' => 1]);

        $csv = "name,email,status,attribute_contacts\nNorthwind,hello@northwind.test,active,not json\n";

        uploadClients($csv)->assertRedirect(route('clients.import.mapping'));
        $response = importClients(identityMapping($csv))->assertSessionHasErrors('file');

        expect(implode(' ', session('errors')->get('file')))->toContain('Line 2');
        expect(Client::query()->count())->toBe(0);
    });

    it('refuses an answer the client form would have refused', function (): void {
        ClientAttribute::factory()->ofType(ClientAttributeType::Number)->create([
            'name' => 'Seats', 'key' => 'seats', 'position' => 1,
        ]);

        $csv = "name,email,status,attribute_seats\nNorthwind,hello@northwind.test,active,about twelve\n";

        uploadClients($csv)->assertRedirect(route('clients.import.mapping'));
        importClients(identityMapping($csv))->assertSessionHasErrors('file');
    });

    it('takes a raw export straight back in, with every answer unchanged', function (): void {
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 1]);
        $seats = ClientAttribute::factory()->ofType(ClientAttributeType::Number)->create(['name' => 'Seats', 'key' => 'seats', 'position' => 2]);
        $vip = ClientAttribute::factory()->ofType(ClientAttributeType::Boolean)->create(['name' => 'VIP', 'key' => 'vip', 'position' => 3]);
        $contacts = ClientAttribute::factory()->nestedRepeater()->create(['name' => 'Contacts', 'key' => 'contacts', 'position' => 4]);

        $client = Client::factory()->create(['name' => 'Northwind', 'email' => 'hello@northwind.test']);
        ClientAttributeValue::factory()->for($client)->of($owner, 'Ana Costa')->create();
        ClientAttributeValue::factory()->for($client)->of($seats, 12)->create();
        ClientAttributeValue::factory()->for($client)->of($vip, true)->create();
        ClientAttributeValue::factory()->for($client)->of($contacts, [
            ['name' => 'Rui', 'addresses' => [['city' => 'Porto']]],
        ])->create();

        $exported = ltrim(actingAs(administrator())
            ->get(route('clients.export', ['mode' => 'raw']))
            ->assertOk()
            ->streamedContent(), "\xEF\xBB\xBF");

        uploadClients($exported)->assertRedirect(route('clients.import.mapping'));
        importClients(identityMapping($exported))->assertSessionHasNoErrors();

        $answers = $client->fresh()->attributeValues->mapWithKeys(fn ($v) => [$v->client_attribute_id => $v->value]);

        expect(Client::query()->count())->toBe(1)
            ->and($answers[$owner->getKey()])->toBe('Ana Costa')
            ->and($answers[$seats->getKey()])->toBe(12)
            ->and($answers[$vip->getKey()])->toBeTrue()
            ->and($answers[$contacts->getKey()][0]['addresses'][0]['city'])->toBe('Porto');
    });
});
