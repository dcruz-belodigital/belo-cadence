<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use App\Support\EssentialAdministration;

final class UserPolicy
{
    public function __construct(
        private readonly EssentialAdministration $essentialAdministration,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::UsersViewAny->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::UsersCreate->value);
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersUpdate->value);
    }

    public function activate(User $user, User $target): bool
    {
        return $user->can(PermissionName::UsersUpdate->value);
    }

    /**
     * Nobody may sign themselves out of the application, and the last person able to
     * administer access may not be deactivated at all.
     */
    public function deactivate(User $user, User $target): bool
    {
        if (! $user->can(PermissionName::UsersUpdate->value)) {
            return false;
        }

        if ($user->is($target)) {
            return false;
        }

        return ! $this->essentialAdministration->deactivationWouldLeaveNoAdministrator($target);
    }

    /**
     * Importing may not be used to perform a mutation the user could not perform by
     * hand, so it also requires the create and update permissions.
     */
    public function import(User $user): bool
    {
        return $user->can(PermissionName::UsersImport->value)
            && $user->can(PermissionName::UsersCreate->value)
            && $user->can(PermissionName::UsersUpdate->value);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::UsersExport->value);
    }
}
