<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\PermissionName;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\NotificationDeliveryFailedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Marks one delivery attempt failed and tells the people who watch history.
 *
 * Both ways of sending end here when something goes wrong — the
 * scheduler and a manual send alike — so a failure is recorded the same way and raises
 * the same internal notification whichever produced it.
 */
final class RecordFailedNotificationDeliveryAction
{
    public function __invoke(
        NotificationDelivery $delivery,
        string $failureMessage,
    ): NotificationDelivery {
        $delivery->update([
            'status' => NotificationDeliveryStatus::Failed,
            'failure_message' => $failureMessage,
        ]);

        Notification::send(
            $this->usersWatchingDeliveries(),
            new NotificationDeliveryFailedNotification($delivery),
        );

        return $delivery;
    }

    /**
     * The active users who are allowed to see delivery history.
     *
     * Permissions are only ever granted through roles, so the roles are queried
     * directly instead of relying on a permission record existing.
     *
     * @return Collection<int, User>
     */
    private function usersWatchingDeliveries(): Collection
    {
        return User::query()
            ->active()
            ->whereHas('roles.permissions', fn (Builder $permissions): Builder => $permissions
                ->where('name', PermissionName::NotificationDeliveriesViewAny->value))
            ->get();
    }
}
