<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\NotificationDelivery;
use App\Models\User;

/**
 * Delivery history is evidence: it can be read and exported, never changed.
 */
final class NotificationDeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::NotificationDeliveriesViewAny->value);
    }

    public function view(User $user, NotificationDelivery $delivery): bool
    {
        return $user->can(PermissionName::NotificationDeliveriesView->value);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::NotificationDeliveriesExport->value);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, NotificationDelivery $delivery): bool
    {
        return false;
    }

    public function delete(User $user, NotificationDelivery $delivery): bool
    {
        return false;
    }
}
