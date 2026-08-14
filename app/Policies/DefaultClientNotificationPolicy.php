<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

final class DefaultClientNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::DefaultClientNotificationsView->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionName::DefaultClientNotificationsUpdate->value);
    }
}
