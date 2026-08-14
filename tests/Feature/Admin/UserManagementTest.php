<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

describe('listing users', function (): void {
    it('lists users with their roles and state', function (): void {
        $user = userWithPermissions([PermissionName::DashboardView], ['name' => 'Bruno Demo']);

        actingAs(administrator())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Bruno Demo')
            ->assertSee($user->roles->first()->name);
    });

    it('searches by name and address', function (): void {
        User::factory()->create(['name' => 'Ada Demo', 'email' => 'ada@example.test']);
        User::factory()->create(['name' => 'Bruno Demo', 'email' => 'bruno@example.test']);

        actingAs(administrator())
            ->get(route('admin.users.index', ['search' => 'ada@']))
            ->assertOk()
            ->assertSee('Ada Demo')
            ->assertDontSee('Bruno Demo');
    });

    it('filters by state and by role', function (): void {
        $role = Role::factory()->create(['name' => 'Coordinator']);

        $active = User::factory()->create(['name' => 'Active Person']);
        $active->assignRole($role);

        User::factory()->inactive()->create(['name' => 'Inactive Person']);

        $administrator = administrator();

        actingAs($administrator)
            ->get(route('admin.users.index', ['state' => 'inactive']))
            ->assertOk()
            ->assertSee('Inactive Person')
            ->assertDontSee('Active Person');

        actingAs($administrator)
            ->get(route('admin.users.index', ['role' => 'Coordinator']))
            ->assertOk()
            ->assertSee('Active Person')
            ->assertDontSee('Inactive Person');
    });
});

describe('creating a user', function (): void {
    it('creates an account with roles and application defaults', function (): void {
        $role = Role::factory()->create(['name' => 'Coordinator']);

        actingAs(administrator())
            ->post(route('admin.users.store'), [
                'name' => 'Bruno Demo',
                'email' => 'Bruno@Example.test',
                'password' => 'a-strong-password-1',
                'password_confirmation' => 'a-strong-password-1',
                'is_active' => '1',
                'roles' => [$role->name],
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'bruno@example.test')->firstOrFail();

        expect($user->name)->toBe('Bruno Demo')
            ->and($user->is_active)->toBeTrue()
            ->and(Hash::check('a-strong-password-1', $user->password))->toBeTrue()
            ->and($user->hasRole('Coordinator'))->toBeTrue()
            ->and($user->timezone->value)->toBe('UTC');

        assertDatabaseHas('audits', ['action' => AuditAction::UserCreated->value, 'auditable_type' => 'user']);
    });

    it('validates the submitted data', function (array $payload, string $field): void {
        actingAs(administrator())
            ->post(route('admin.users.store'), $payload)
            ->assertSessionHasErrors($field);
    })->with([
        'missing name' => [['email' => 'a@b.test', 'password' => 'a-strong-password-1', 'password_confirmation' => 'a-strong-password-1'], 'name'],
        'invalid email' => [['name' => 'A', 'email' => 'nope', 'password' => 'a-strong-password-1', 'password_confirmation' => 'a-strong-password-1'], 'email'],
        'short password' => [['name' => 'A', 'email' => 'a@b.test', 'password' => 'short', 'password_confirmation' => 'short'], 'password'],
        'mismatched confirmation' => [['name' => 'A', 'email' => 'a@b.test', 'password' => 'a-strong-password-1', 'password_confirmation' => 'different-password-1'], 'password'],
        'unknown role' => [['name' => 'A', 'email' => 'a@b.test', 'password' => 'a-strong-password-1', 'password_confirmation' => 'a-strong-password-1', 'roles' => ['Wizard']], 'roles.0'],
    ]);

    it('refuses an address another account already uses', function (): void {
        User::factory()->create(['email' => 'bruno@example.test']);

        actingAs(administrator())
            ->post(route('admin.users.store'), [
                'name' => 'Bruno Again',
                'email' => 'bruno@example.test',
                'password' => 'a-strong-password-1',
                'password_confirmation' => 'a-strong-password-1',
            ])
            ->assertSessionHasErrors('email');
    });
});

describe('updating a user', function (): void {
    it('updates the details and the roles', function (): void {
        $role = Role::factory()->create(['name' => 'Coordinator']);
        $user = User::factory()->create(['name' => 'Old Name']);

        actingAs(administrator())
            ->put(route('admin.users.update', $user), [
                'name' => 'New Name',
                'email' => $user->email->value,
                'roles' => [$role->name],
            ])
            ->assertRedirect(route('admin.users.show', $user));

        $user->refresh();

        expect($user->name)->toBe('New Name')
            ->and($user->hasRole('Coordinator'))->toBeTrue();

        $audit = Audit::query()->where('action', AuditAction::UserUpdated->value)->firstOrFail();

        expect($audit->new_values['roles'])->toBe(['Coordinator']);
    });

    it('leaves the password alone when the field is empty', function (): void {
        $user = User::factory()->create();
        $original = $user->password;

        actingAs(administrator())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email->value,
                'password' => '',
            ])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->password)->toBe($original);
    });

    it('changes the password when one is given', function (): void {
        $user = User::factory()->create();

        actingAs(administrator())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email->value,
                'password' => 'another-strong-password-1',
                'password_confirmation' => 'another-strong-password-1',
            ])
            ->assertSessionHasNoErrors();

        expect(Hash::check('another-strong-password-1', $user->fresh()->password))->toBeTrue();
    });

    it('never records a password in the audit log', function (): void {
        $user = User::factory()->create();

        actingAs(administrator())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email->value,
                'password' => 'another-strong-password-1',
                'password_confirmation' => 'another-strong-password-1',
            ]);

        $audit = Audit::query()->where('action', AuditAction::UserUpdated->value)->firstOrFail();

        expect(json_encode($audit->new_values))->not->toContain('another-strong-password-1')
            ->and($audit->metadata)->toBe(['password_changed' => true]);
    });
});

describe('activating and deactivating', function (): void {
    it('deactivates another account', function (): void {
        $user = User::factory()->create();

        actingAs(administrator())
            ->post(route('admin.users.deactivate', $user))
            ->assertRedirect();

        expect($user->fresh()->is_active)->toBeFalse();

        assertDatabaseHas('audits', ['action' => AuditAction::UserDeactivated->value]);
    });

    it('activates a deactivated account', function (): void {
        $user = User::factory()->inactive()->create();

        actingAs(administrator())
            ->post(route('admin.users.activate', $user))
            ->assertRedirect();

        expect($user->fresh()->is_active)->toBeTrue();

        assertDatabaseHas('audits', ['action' => AuditAction::UserActivated->value]);
    });

    it('refuses to let somebody deactivate themselves', function (): void {
        $administrator = administrator();

        actingAs($administrator)
            ->post(route('admin.users.deactivate', $administrator))
            ->assertForbidden();

        expect($administrator->fresh()->is_active)->toBeTrue();
    });

    it('refuses to deactivate the last person able to administer access', function (): void {
        $onlyAdministrator = administrator();
        $anotherAdministrator = administrator();

        // With two administrators, one of them may still be deactivated.
        actingAs($anotherAdministrator)
            ->post(route('admin.users.deactivate', $onlyAdministrator))
            ->assertRedirect();

        expect($onlyAdministrator->fresh()->is_active)->toBeFalse();

        // Now only one is left, and nobody may remove them.
        $helper = userWithPermissions([PermissionName::UsersUpdate, PermissionName::UsersViewAny]);

        actingAs($helper)
            ->post(route('admin.users.deactivate', $anotherAdministrator))
            ->assertForbidden();

        expect($anotherAdministrator->fresh()->is_active)->toBeTrue();
    });

    it('refuses a role change that would leave nobody able to administer access', function (): void {
        $administrator = administrator();
        $helper = userWithPermissions([PermissionName::UsersUpdate, PermissionName::UsersViewAny]);

        actingAs($helper)
            ->put(route('admin.users.update', $administrator), [
                'name' => $administrator->name,
                'email' => $administrator->email->value,
                'roles' => [],
            ])
            ->assertSessionHasErrors('roles');

        expect($administrator->fresh()->roles)->toHaveCount(1);
    });
});

describe('there is no user deletion', function (): void {
    it('has no destroy route', function (): void {
        expect(Route::has('admin.users.destroy'))->toBeFalse();
    });

    it('keeps audit history when an account is deactivated', function (): void {
        $user = administrator();
        $target = User::factory()->create();

        actingAs($user)->post(route('admin.users.deactivate', $target));

        $auditCount = Audit::query()->where('user_id', $user->getKey())->count();

        actingAs(administrator())->post(route('admin.users.deactivate', $user));

        expect(Audit::query()->where('user_id', $user->getKey())->count())->toBe($auditCount);
    });
});
