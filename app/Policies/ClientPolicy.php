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
     * Whether this user may send email by hand at all.
     *
     * Answered without a client so the entry points and the send page can be offered
     * before one has been chosen; `notify()` decides the individual client.
     */
    public function notifyAny(User $user): bool
    {
        return $user->can(PermissionName::NotificationsSend->value);
    }

    /**
     * Whether this client may be emailed by hand right now.
     *
     * The scheduler refuses to email an archived or inactive client, and a manual send
     * is held to the same rule: marking a client inactive is how somebody says "stop
     * emailing them", and a button must not quietly override that.
     */
    public function notify(User $user, Client $client): bool
    {
        return $this->notifyAny($user)
            && ! $client->trashed()
            && $client->isActive();
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
