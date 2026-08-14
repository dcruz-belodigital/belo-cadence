<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

final class ApplicationSettingsPolicy
{
    public function view(User $user): bool
    {
        return $user->can(PermissionName::ApplicationSettingsView->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionName::ApplicationSettingsUpdate->value);
    }
}
