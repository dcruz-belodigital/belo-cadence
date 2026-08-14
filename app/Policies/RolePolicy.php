<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Role;
use App\Models\User;
use App\Support\EssentialAdministration;

final class RolePolicy
{
    public function __construct(
        private readonly EssentialAdministration $essentialAdministration,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::RolesViewAny->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionName::RolesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::RolesCreate->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionName::RolesUpdate->value);
    }

    /**
     * A role can only be removed once nobody holds it and the application would still
     * have somebody able to administer access.
     */
    public function delete(User $user, Role $role): bool
    {
        if (! $user->can(PermissionName::RolesDelete->value)) {
            return false;
        }

        if ($role->users()->exists()) {
            return false;
        }

        return ! $this->essentialAdministration->roleDeletionWouldLeaveNoAdministrator($role);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::RolesExport->value);
    }
}
