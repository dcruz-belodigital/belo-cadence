<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\User;

final class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ClientsViewAny->value);
    }

    public function view(User $user, Client $client): bool
    {
        return $user->can(PermissionName::ClientsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ClientsCreate->value);
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can(PermissionName::ClientsUpdate->value);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can(PermissionName::ClientsDelete->value);
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->can(PermissionName::ClientsDelete->value);
    }

    /**
     * Importing may not be used to perform a mutation the user could not perform by
     * hand, so it also requires the create and update permissions.
     */
    public function import(User $user): bool
    {
        return $user->can(PermissionName::ClientsImport->value)
            && $user->can(PermissionName::ClientsCreate->value)
            && $user->can(PermissionName::ClientsUpdate->value);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::ClientsExport->value);
    }
}
