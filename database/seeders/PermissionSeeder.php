<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Mirrors the PermissionName enum into the database.
 *
 * Permissions are source code; this seeder only makes them available to roles.
 */
final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // Roles are assigned permissions straight after this, so the cached permission
        // list has to be dropped now rather than on the next request.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
