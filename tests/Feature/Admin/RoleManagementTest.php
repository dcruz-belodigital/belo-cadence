<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('lists roles with their permission and user counts', function (): void {
    $user = userWithPermissions([PermissionName::DashboardView]);

    actingAs(administrator())
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee($user->roles->first()->name);
});

it('creates a role with the chosen permissions', function (): void {
    actingAs(administrator())
        ->post(route('admin.roles.store'), [
            'name' => 'Coordinator',
            'permissions' => [
                PermissionName::ClientsViewAny->value,
                PermissionName::ClientsView->value,
            ],
        ])
        ->assertRedirect();

    $role = Role::query()->where('name', 'Coordinator')->firstOrFail();

    expect($role->permissions->pluck('name')->all())
        ->toBe([PermissionName::ClientsViewAny->value, PermissionName::ClientsView->value]);

    assertDatabaseHas('audits', ['action' => AuditAction::RoleCreated->value, 'auditable_type' => 'role']);
});

it('refuses a permission name the application does not declare', function (): void {
    actingAs(administrator())
        ->post(route('admin.roles.store'), [
            'name' => 'Wizard',
            'permissions' => ['clients.summon'],
        ])
        ->assertSessionHasErrors('permissions.0');

    expect(Role::query()->where('name', 'Wizard')->exists())->toBeFalse();
});

it('refuses a duplicate role name', function (): void {
    Role::factory()->create(['name' => 'Coordinator']);

    actingAs(administrator())
        ->post(route('admin.roles.store'), ['name' => 'Coordinator'])
        ->assertSessionHasErrors('name');
});

it('renames a role and changes its permissions', function (): void {
    $role = Role::factory()->create(['name' => 'Coordinator']);
    $role->givePermissionTo(Permission::findOrCreate(PermissionName::ClientsViewAny->value, 'web'));

    actingAs(administrator())
        ->put(route('admin.roles.update', $role), [
            'name' => 'Client Coordinator',
            'permissions' => [PermissionName::ClientsViewAny->value, PermissionName::ClientsCreate->value],
        ])
        ->assertRedirect(route('admin.roles.show', $role));

    $role->refresh();

    expect($role->name)->toBe('Client Coordinator')
        ->and($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe([PermissionName::ClientsCreate->value, PermissionName::ClientsViewAny->value]);

    $audit = Audit::query()->where('action', AuditAction::RoleUpdated->value)->firstOrFail();

    expect($audit->old_values['permissions'])->toBe([PermissionName::ClientsViewAny->value]);
});

it('removes permissions that are left out', function (): void {
    $role = Role::factory()->create();
    $role->givePermissionTo(Permission::findOrCreate(PermissionName::ClientsViewAny->value, 'web'));

    actingAs(administrator())
        ->put(route('admin.roles.update', $role), ['name' => $role->name, 'permissions' => []])
        ->assertRedirect();

    expect($role->fresh()->permissions)->toHaveCount(0);
});

it('applies a role change to the people holding it', function (): void {
    $user = userWithPermissions([PermissionName::DashboardView]);
    $role = $user->roles->first();

    expect($user->can(PermissionName::ClientsViewAny->value))->toBeFalse();

    actingAs(administrator())
        ->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'permissions' => [PermissionName::DashboardView->value, PermissionName::ClientsViewAny->value],
        ]);

    actingAs($user->fresh())
        ->get(route('clients.index'))
        ->assertOk();
});

it('refuses a permission change that would leave nobody able to administer access', function (): void {
    $administrator = administrator();
    $role = $administrator->roles->first();

    actingAs($administrator)
        ->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'permissions' => [PermissionName::DashboardView->value],
        ])
        ->assertSessionHasErrors('permissions');

    expect($role->fresh()->hasPermissionTo(PermissionName::RolesUpdate->value))->toBeTrue();
});

it('deletes a role nobody holds', function (): void {
    administrator();
    $role = Role::factory()->create(['name' => 'Unused']);

    actingAs(administrator())
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::query()->where('name', 'Unused')->exists())->toBeFalse();

    assertDatabaseHas('audits', ['action' => AuditAction::RoleDeleted->value]);
});

it('refuses to delete a role that is still held by somebody', function (): void {
    $role = Role::factory()->create(['name' => 'In Use']);
    User::factory()->create()->assignRole($role);

    actingAs(administrator())
        ->delete(route('admin.roles.destroy', $role))
        ->assertForbidden();

    expect(Role::query()->where('name', 'In Use')->exists())->toBeTrue();
});

it('refuses to delete the last role able to administer access', function (): void {
    $administrator = administrator();
    $administratorRole = $administrator->roles->first();

    // Nobody holds it any more, but it is still the only way back in.
    $administrator->removeRole($administratorRole);

    $deleter = userWithPermissions([PermissionName::RolesViewAny, PermissionName::RolesDelete]);

    actingAs($deleter)
        ->delete(route('admin.roles.destroy', $administratorRole))
        ->assertForbidden();

    expect(Role::query()->whereKey($administratorRole->getKey())->exists())->toBeTrue();
});

it('shows which people hold a role', function (): void {
    $role = Role::factory()->create(['name' => 'Coordinator']);
    User::factory()->create(['name' => 'Bruno Demo'])->assignRole($role);

    actingAs(administrator())
        ->get(route('admin.roles.show', $role))
        ->assertOk()
        ->assertSee('Bruno Demo');
});
