<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Permissions are only ever granted through roles, so tests build a throwaway
| role holding exactly the permissions the case is about. That keeps every
| authorisation test honest about what it is really exercising.
|
*/

/**
 * @param  list<PermissionName|string>  $permissions
 * @param  array<string, mixed>  $attributes
 */
function userWithPermissions(array $permissions, array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    $role = Role::create([
        'name' => 'Test role '.Str::random(8),
        'guard_name' => 'web',
    ]);

    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::findOrCreate(
            $permission instanceof PermissionName ? $permission->value : $permission,
            'web',
        ));
    }

    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

/**
 * A user holding every permission the application declares.
 *
 * @param  array<string, mixed>  $attributes
 */
function administrator(array $attributes = []): User
{
    return userWithPermissions(PermissionName::cases(), $attributes);
}

/**
 * A user holding every permission except the ones given, for negative authorisation tests.
 *
 * @param  list<PermissionName>  $except
 * @param  array<string, mixed>  $attributes
 */
function administratorWithout(array $except, array $attributes = []): User
{
    $permissions = array_values(array_filter(
        PermissionName::cases(),
        static fn (PermissionName $permission): bool => ! in_array($permission, $except, true),
    ));

    return userWithPermissions($permissions, $attributes);
}
