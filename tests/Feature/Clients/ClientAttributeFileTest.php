<?php

declare(strict_types=1);

use App\Actions\ClientAttributes\DeleteClientAttributeAction;
use App\Enums\ClientAttributeType;
use App\Enums\ClientStatus;
use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use App\Models\ClientAttributeValue;
use App\ValueObjects\TemplateBinding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

/**
 * A file attribute is the one answer that is not typed. Its bytes live on the private
 * disk, the answer is a reference to the row that owns them, and the two are only ever
 * destroyed together.
 *
 * `Storage::fake` replaces the local disk, so what these tests assert about the disk is
 * what the application really wrote and really deleted.
 */
beforeEach(function (): void {
    Storage::fake(ClientAttributeFile::DISK);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function fileClientPayload(array $overrides = []): array
{
    return [
        'name' => 'Northwind Studio',
        'email' => 'hello@northwind.test',
        'status' => ClientStatus::Active->value,
        ...$overrides,
    ];
}

function pdfUpload(string $name = 'contract.pdf'): UploadedFile
{
    // A real PDF header, because the rule guesses the type from the contents.
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n%Cadence test\n");
}

describe('uploading', function (): void {
    it('stores the bytes and answers with a reference to them', function (): void {
        $contract = ClientAttribute::factory()->ofType(ClientAttributeType::File)->create(['name' => 'Signed contract']);

        actingAs(administrator())
            ->post(route('clients.store'), fileClientPayload([
                'client_attributes' => [$contract->getKey() => ['file' => pdfUpload()]],
            ]))
            ->assertSessionHasNoErrors();

        $file = ClientAttributeFile::query()->firstOrFail();
        $answer = ClientAttributeValue::query()->firstOrFail();

        expect($file->original_name)->toBe('contract.pdf')
            ->and($file->client_attribute_id)->toBe($contract->getKey())
            ->and($answer->value)->toBe(['id' => $file->getKey(), 'name' => 'contract.pdf', 'size' => $file->size]);

        Storage::disk(ClientAttributeFile::DISK)->assertExists($file->path);
    });

    it('keeps the file it already has when nothing new is chosen', function (): void {
        [$client, $contract, $file] = clientHoldingAFile();

        actingAs(administrator())
            ->put(route('clients.update', $client), fileClientPayload([
                'name' => 'Renamed',
                'client_attributes' => [$contract->getKey() => ['keep' => (string) $file->getKey()]],
            ]))
            ->assertSessionHasNoErrors();

        expect(ClientAttributeFile::query()->count())->toBe(1);
        Storage::disk(ClientAttributeFile::DISK)->assertExists($file->path);
    });

    it('deletes the file it replaces, bytes and all', function (): void {
        [$client, $contract, $old] = clientHoldingAFile();

        actingAs(administrator())
            ->put(route('clients.update', $client), fileClientPayload([
                'client_attributes' => [$contract->getKey() => [
                    'keep' => (string) $old->getKey(),
                    'file' => pdfUpload('replacement.pdf'),
                ]],
            ]))
            ->assertSessionHasNoErrors();

        $new = ClientAttributeFile::query()->firstOrFail();

        expect(ClientAttributeFile::query()->count())->toBe(1)
            ->and($new->original_name)->toBe('replacement.pdf');

        Storage::disk(ClientAttributeFile::DISK)->assertMissing($old->path);
        Storage::disk(ClientAttributeFile::DISK)->assertExists($new->path);
    });

    it('deletes the file when the answer is cleared', function (): void {
        [$client, $contract, $file] = clientHoldingAFile();

        actingAs(administrator())
            ->put(route('clients.update', $client), fileClientPayload([
                'client_attributes' => [$contract->getKey() => ['keep' => '']],
            ]))
            ->assertSessionHasNoErrors();

        assertDatabaseCount('client_attribute_files', 0);
        assertDatabaseMissing('client_attribute_values', ['client_attribute_id' => $contract->getKey()]);
        Storage::disk(ClientAttributeFile::DISK)->assertMissing($file->path);
    });

    it('refuses a file of a type that is not accepted', function (): void {
        $contract = ClientAttribute::factory()->ofType(ClientAttributeType::File)->create(['name' => 'Signed contract']);

        actingAs(administrator())
            ->post(route('clients.store'), fileClientPayload([
                'client_attributes' => [$contract->getKey() => [
                    'file' => UploadedFile::fake()->createWithContent('payload.php', '<?php echo 1;'),
                ]],
            ]))
            ->assertSessionHasErrors('client_attributes.'.$contract->getKey());

        assertDatabaseCount('client_attribute_files', 0);
    });

    it('refuses a file over the size limit', function (): void {
        $contract = ClientAttribute::factory()->ofType(ClientAttributeType::File)->create(['name' => 'Signed contract']);

        actingAs(administrator())
            ->post(route('clients.store'), fileClientPayload([
                'client_attributes' => [$contract->getKey() => [
                    'file' => UploadedFile::fake()->create('huge.pdf', ClientAttributeFile::MAX_KILOBYTES + 1, 'application/pdf'),
                ]],
            ]))
            ->assertSessionHasErrors('client_attributes.'.$contract->getKey());

        assertDatabaseCount('client_attribute_files', 0);
    });

    it('ignores a kept file belonging to somebody else', function (): void {
        [, $contract, $theirs] = clientHoldingAFile();
        $client = Client::factory()->create();

        actingAs(administrator())
            ->put(route('clients.update', $client), fileClientPayload([
                'email' => 'other@northwind.test',
                'client_attributes' => [$contract->getKey() => ['keep' => (string) $theirs->getKey()]],
            ]))
            ->assertSessionHasNoErrors();

        // Their file is untouched, and this client answered nothing.
        expect(ClientAttributeFile::query()->count())->toBe(1);
        assertDatabaseMissing('client_attribute_values', ['client_id' => $client->getKey()]);
    });
});

describe('a file inside repeating rows', function (): void {
    it('stores one file per row', function (): void {
        $certificates = ClientAttribute::factory()->repeaterOfFiles()->create(['name' => 'Certificates']);

        actingAs(administrator())
            ->post(route('clients.store'), fileClientPayload([
                'client_attributes' => [$certificates->getKey() => [
                    ['label' => 'ISO 9001', 'document' => ['file' => pdfUpload('iso.pdf')]],
                    ['label' => 'Insurance', 'document' => ['file' => pdfUpload('policy.pdf')]],
                ]],
            ]))
            ->assertSessionHasNoErrors();

        $rows = ClientAttributeValue::query()->firstOrFail()->value;

        expect(ClientAttributeFile::query()->count())->toBe(2)
            ->and($rows)->toHaveCount(2)
            ->and($rows[0]['document']['name'])->toBe('iso.pdf')
            ->and($rows[1]['document']['name'])->toBe('policy.pdf');
    });

    /*
    | The row nobody typed in is dropped before validation, and a row holding only a file
    | has nothing in the input to prove it exists. It survives because the upload itself
    | is read in as the answer is normalised, rather than laid back over it afterwards.
    */
    it('keeps a row whose only content is the file', function (): void {
        $certificates = ClientAttribute::factory()->repeaterOfFiles()->create(['name' => 'Certificates']);

        actingAs(administrator())
            ->post(route('clients.store'), fileClientPayload([
                'client_attributes' => [$certificates->getKey() => [
                    ['label' => '', 'document' => ['keep' => '', 'file' => pdfUpload('only.pdf')]],
                ]],
            ]))
            ->assertSessionHasNoErrors();

        $rows = ClientAttributeValue::query()->firstOrFail()->value;

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['document']['name'])->toBe('only.pdf');
    });

    it('deletes the file of a row that was removed', function (): void {
        $certificates = ClientAttribute::factory()->repeaterOfFiles()->create(['name' => 'Certificates']);
        $client = Client::factory()->create(['email' => 'hello@northwind.test']);

        actingAs(administrator())->put(route('clients.update', $client), fileClientPayload([
            'client_attributes' => [$certificates->getKey() => [
                ['label' => 'ISO 9001', 'document' => ['file' => pdfUpload('iso.pdf')]],
                ['label' => 'Insurance', 'document' => ['file' => pdfUpload('policy.pdf')]],
            ]],
        ]))->assertSessionHasNoErrors();

        $kept = ClientAttributeFile::query()->where('original_name', 'iso.pdf')->firstOrFail();
        $dropped = ClientAttributeFile::query()->where('original_name', 'policy.pdf')->firstOrFail();

        // The second row is gone; the first keeps the file it already had.
        actingAs(administrator())->put(route('clients.update', $client), fileClientPayload([
            'client_attributes' => [$certificates->getKey() => [
                ['label' => 'ISO 9001', 'document' => ['keep' => (string) $kept->getKey()]],
            ]],
        ]))->assertSessionHasNoErrors();

        expect(ClientAttributeFile::query()->pluck('id')->all())->toBe([$kept->getKey()]);
        Storage::disk(ClientAttributeFile::DISK)->assertMissing($dropped->path);
        Storage::disk(ClientAttributeFile::DISK)->assertExists($kept->path);
    });
});

describe('downloading', function (): void {
    it('serves the file to somebody who may read the client', function (): void {
        [, , $file] = clientHoldingAFile();

        $response = actingAs(administrator())->get(route('clients.files.show', $file));

        $response->assertOk()->assertDownload('contract.pdf');
    });

    it('refuses somebody who may not read clients', function (): void {
        [, , $file] = clientHoldingAFile();

        actingAs(administratorWithout([PermissionName::ClientsView]))
            ->get(route('clients.files.show', $file))
            ->assertForbidden();
    });

    it('sends a guest to the login page rather than the file', function (): void {
        [, , $file] = clientHoldingAFile();

        get(route('clients.files.show', $file))->assertRedirect(route('login'));
    });

    /*
    | The local disk is configured with `serve`, which registers `/storage/{path}`. That
    | route is signature-gated for a private disk and nothing here ever signs one, so the
    | download route stays the only way in — and this is what says so out loud.
    */
    it('does not serve the bytes from the disk route', function (): void {
        [, , $file] = clientHoldingAFile();

        $response = actingAs(administrator())->get('/storage/'.$file->path);

        expect($response->status())->not->toBe(200);
    });

    it('answers with nothing when the bytes have gone', function (): void {
        [, , $file] = clientHoldingAFile();

        Storage::disk(ClientAttributeFile::DISK)->delete($file->path);

        actingAs(administrator())->get(route('clients.files.show', $file))->assertNotFound();
    });
});

describe('the pages that show a file', function (): void {
    it('links to it from the client page and from the form', function (): void {
        [$client, , $file] = clientHoldingAFile();

        $link = route('clients.files.show', $file);

        actingAs(administrator())->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee($link, false)
            ->assertSee('contract.pdf');

        // The form names what is already there, so it can be kept or replaced.
        actingAs(administrator())->get(route('clients.edit', $client))
            ->assertOk()
            ->assertSee('contract.pdf');
    });

    it('offers the file as an attachment on the schedule form', function (): void {
        [$client, $attribute] = clientHoldingAFile();

        actingAs(administrator())->get(route('clients.schedules.create', $client))
            ->assertOk()
            ->assertSee(TemplateBinding::attribute($attribute->getKey())->token(), false)
            ->assertSee('Signed contract');
    });
});

describe('deleting the attribute', function (): void {
    it('takes every file uploaded for it', function (): void {
        [, $contract, $file] = clientHoldingAFile();

        app(DeleteClientAttributeAction::class)($contract);

        assertDatabaseCount('client_attribute_files', 0);
        Storage::disk(ClientAttributeFile::DISK)->assertMissing($file->path);
    });
});

describe('archiving a client', function (): void {
    it('keeps its files, because the client comes back whole', function (): void {
        [$client, , $file] = clientHoldingAFile();

        actingAs(administrator())->delete(route('clients.destroy', $client))->assertRedirect();

        expect(ClientAttributeFile::query()->count())->toBe(1);
        Storage::disk(ClientAttributeFile::DISK)->assertExists($file->path);
    });
});

/**
 * A client that has already uploaded one file, answered and all.
 *
 * @return array{0: Client, 1: ClientAttribute, 2: ClientAttributeFile}
 */
function clientHoldingAFile(): array
{
    $attribute = ClientAttribute::factory()->ofType(ClientAttributeType::File)->create(['name' => 'Signed contract']);
    $client = Client::factory()->create(['email' => 'hello@northwind.test']);

    $file = ClientAttributeFile::factory()->stored()->create([
        'client_id' => $client->getKey(),
        'client_attribute_id' => $attribute->getKey(),
    ]);

    ClientAttributeValue::factory()->for($client)->of($attribute, $file->toValue()->toArray())->create();

    return [$client, $attribute, $file];
}
