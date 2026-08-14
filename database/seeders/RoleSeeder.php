<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The roles every installation starts with.
 *
 * Both are kept in sync with the permission list rather than fixed at the set that
 * existed when they were written, so a newly added permission is never left
 * unassignable and never quietly widens what Viewer can do.
 */
final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate($this->administratorRoleName(), 'web')
            ->syncPermissions(PermissionName::values());

        Role::findOrCreate('Viewer', 'web')
            ->syncPermissions(PermissionName::readOnlyValues());
    }

    private function administratorRoleName(): string
    {
        return (string) config('cadence.administrator_role');
    }
}
