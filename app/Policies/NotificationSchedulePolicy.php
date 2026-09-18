<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\NotificationTarget;
use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\NotificationSchedule;
use App\Models\User;

final class NotificationSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::NotificationsViewAny->value);
    }

    public function view(User $user, NotificationSchedule $schedule): bool
    {
        return $user->can(PermissionName::NotificationsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::NotificationsCreate->value);
    }

    public function update(User $user, NotificationSchedule $schedule): bool
    {
        return $user->can(PermissionName::NotificationsUpdate->value);
    }

    public function delete(User $user, NotificationSchedule $schedule): bool
    {
        return $user->can(PermissionName::NotificationsDelete->value);
    }

    public function export(User $user): bool
    {
        return $user->can(PermissionName::NotificationsExport->value);
    }

    /**
     * Whether this schedule may be sent by hand right now.
     *
     * The same permission as the ad-hoc send form, and the same refusal: a schedule that
     * belongs to an archived or inactive client may not be fired off manually either,
     * because marking a client inactive is how somebody says "stop emailing them".
     */
    public function send(User $user, NotificationSchedule $schedule): bool
    {
        if (! $user->can(PermissionName::NotificationsSend->value)) {
            return false;
        }

        $client = $schedule->client;

        return $schedule->target === NotificationTarget::Recipients
            || ($client instanceof Client && ! $client->trashed() && $client->isActive());
    }
}
