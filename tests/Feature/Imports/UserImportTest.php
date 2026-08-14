<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    Storage::fake('local');
});

function userCsvUpload(string $contents): UploadedFile
{
    return UploadedFile::fake()->createWithContent('users.csv', $contents);
}

function uploadUsers(string $csv, ?User $as = null): TestResponse
{
    return actingAs($as ?? administrator())
        ->post(route('admin.users.import.store'), ['file' => userCsvUpload($csv)]);
}

/**
 * @param  array<string, string|null>  $mapping
 */
function importUsers(array $mapping, ?User $as = null): TestResponse
{
    return actingAs($as ?? administrator())
        ->post(route('admin.users.import.run'), ['mapping' => $mapping]);
}

/**
 * @return array<string, string>
 */
function userIdentityMapping(string $csv): array
{
    $headings = str_getcsv((string) strtok(trim($csv), "\n"), escape: '');

    return array_combine($headings, $headings);
}

it('offers a template with the expected columns', function (): void {
    $response = actingAs(administrator())->get(route('admin.users.import.template'))->assertOk();

    expect(ltrim($response->streamedContent(), "\xEF\xBB\xBF"))
        ->toContain('id,name,email,password,is_active,roles');
});

it('creates users with hashed passwords and roles', function (): void {
    Role::factory()->create(['name' => 'Coordinator']);

    $csv = <<<'CSV'
    id,name,email,password,is_active,roles
    ,Bruno Demo,bruno@example.test,import-password-123,1,Coordinator
    CSV;

    uploadUsers($csv);

    importUsers(userIdentityMapping($csv))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'bruno@example.test')->firstOrFail();

    expect($user->name)->toBe('Bruno Demo')
        ->and($user->is_active)->toBeTrue()
        ->and($user->password)->not->toBe('import-password-123')
        ->and(Hash::check('import-password-123', $user->password))->toBeTrue()
        ->and($user->hasRole('Coordinator'))->toBeTrue();

    assertDatabaseHas('audits', ['action' => AuditAction::UserCreated->value]);
    assertDatabaseHas('audits', ['action' => AuditAction::UsersImported->value]);
});

it('matches headings written for people', function (): void {
    $csv = "Full name,Email address,Password\nBruno Demo,bruno@example.test,import-password-123\n";

    uploadUsers($csv);

    actingAs(administrator())
        ->get(route('admin.users.import.mapping'))
        ->assertOk()
        // The application's own labels are matched as readily as its column names.
        ->assertSee('Full name')
        ->assertSee('Email address');

    importUsers([
        'name' => 'Full name',
        'email' => 'Email address',
        'password' => 'Password',
    ])->assertRedirect(route('admin.users.index'));

    expect(User::query()->where('email', 'bruno@example.test')->exists())->toBeTrue();
});

it('creates a deactivated user when the column says so', function (): void {
    $csv = "name,email,password,is_active\nDalia Demo,dalia@example.test,import-password-123,0\n";

    uploadUsers($csv);
    importUsers(userIdentityMapping($csv));

    expect(User::query()->where('email', 'dalia@example.test')->firstOrFail()->is_active)->toBeFalse();
});

it('keeps an existing password when updating and the column is empty', function (): void {
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'bruno@example.test']);
    $originalPassword = $user->password;

    $csv = "id,name,email,password\n{$user->getKey()},New Name,bruno@example.test,\n";

    uploadUsers($csv);

    importUsers(userIdentityMapping($csv))->assertSessionHas('success');

    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->fresh()->password)->toBe($originalPassword);
});

it('requires a password for a new user', function (): void {
    $csv = "name,email,password\nBruno Demo,bruno@example.test,\n";

    uploadUsers($csv);
    importUsers(userIdentityMapping($csv))->assertSessionHasErrors('file');

    expect(User::query()->where('email', 'bruno@example.test')->exists())->toBeFalse();
});

it('rejects a weak password', function (): void {
    $csv = "name,email,password\nBruno Demo,bruno@example.test,short\n";

    uploadUsers($csv);
    importUsers(userIdentityMapping($csv))->assertSessionHasErrors('file');
});

it('rejects a role that does not exist', function (): void {
    $csv = "name,email,password,roles\nBruno Demo,bruno@example.test,import-password-123,Wizard\n";

    uploadUsers($csv);
    importUsers(userIdentityMapping($csv))->assertSessionHasErrors('file');

    expect(User::query()->where('email', 'bruno@example.test')->exists())->toBeFalse();
});

it('rejects an address another account already uses', function (): void {
    User::factory()->create(['email' => 'bruno@example.test']);

    $csv = "name,email,password\nBruno Again,bruno@example.test,import-password-123\n";

    uploadUsers($csv);
    importUsers(userIdentityMapping($csv))->assertSessionHasErrors('file');
});

it('refuses a user who may import but not create accounts', function (): void {
    $user = administratorWithout([PermissionName::UsersCreate]);

    uploadUsers("name,email,password\nBruno Demo,bruno@example.test,import-password-123\n", $user)
        ->assertForbidden();

    expect(User::query()->where('email', 'bruno@example.test')->exists())->toBeFalse();
});

it('refuses the matching step to a user without the import permission', function (): void {
    $user = administratorWithout([PermissionName::UsersImport]);

    actingAs($user)->get(route('admin.users.import.mapping'))->assertForbidden();
    importUsers(['name' => 'name'], $user)->assertForbidden();
});
