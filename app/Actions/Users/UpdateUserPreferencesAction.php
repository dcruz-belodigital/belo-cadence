<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\UpdateUserPreferencesData;
use App\Models\User;

/**
 * Stores a person's own display preferences.
 *
 * Preferences are not audited: they change nothing about the business record, only how
 * the application looks to one person.
 */
final class UpdateUserPreferencesAction
{
    public function __invoke(User $user, UpdateUserPreferencesData $data): User
    {
        $user->update([
            'color_scheme' => $data->colorScheme,
            'theme' => $data->theme,
            'locale' => $data->locale,
            'timezone' => $data->timezone,
        ]);

        return $user;
    }
}
