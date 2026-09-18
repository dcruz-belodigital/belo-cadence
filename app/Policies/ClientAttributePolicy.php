<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ClientAttribute;
use App\Models\User;

final class ClientAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ClientAttributesViewAny->value);
    }

    public function view(User $user, ClientAttribute $attribute): bool
    {
        return $user->can(PermissionName::ClientAttributesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ClientAttributesCreate->value);
    }

    public function update(User $user, ClientAttribute $attribute): bool
    {
        return $user->can(PermissionName::ClientAttributesUpdate->value);
    }

    /**
     * Deleting an attribute deletes every answer recorded against it.
     *
     * Unlike a role, which cannot be removed while anybody holds it, this is allowed —
     * refusing would leave no way to remove an attribute that has ever been filled in.
     * The confirmation names how many clients are affected instead, and deactivating is
     * offered as the reversible alternative.
     */
    public function delete(User $user, ClientAttribute $attribute): bool
    {
        return $user->can(PermissionName::ClientAttributesDelete->value);
    }
}
