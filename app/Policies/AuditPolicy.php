<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\User;

/**
 * The audit log is read-only from the application: entries are only ever created by
 * the operations they describe.
 */
final class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::AuditLogViewAny->value);
    }

    public function view(User $user, Audit $audit): bool
    {
        return $user->can(PermissionName::AuditLogView->value);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::AuditLogExport->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Audit $audit): bool
    {
        return false;
    }

    public function delete(User $user, Audit $audit): bool
    {
        return false;
    }
}
