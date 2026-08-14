<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Client;
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
