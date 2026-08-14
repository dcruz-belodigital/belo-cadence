<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ClientNotificationSchedule;
use App\Models\User;

final class ClientNotificationSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ClientNotificationsViewAny->value);
    }

    public function view(User $user, ClientNotificationSchedule $schedule): bool
    {
        return $user->can(PermissionName::ClientNotificationsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ClientNotificationsCreate->value);
    }

    public function update(User $user, ClientNotificationSchedule $schedule): bool
    {
        return $user->can(PermissionName::ClientNotificationsUpdate->value);
    }

    public function delete(User $user, ClientNotificationSchedule $schedule): bool
    {
        return $user->can(PermissionName::ClientNotificationsDelete->value);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::ClientNotificationsExport->value);
    }
}
