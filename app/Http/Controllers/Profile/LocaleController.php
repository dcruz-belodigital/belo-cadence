<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Users\UpdateUserPreferencesAction;
use App\Data\Users\UpdateUserPreferencesData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;

/**
 * The language switcher in the account menu. It writes the same preference the profile
 * page does, leaving the person's other preferences untouched.
 */
final class LocaleController extends Controller
{
    public function update(
        UpdateLocaleRequest $request,
        UpdateUserPreferencesAction $updatePreferences,
    ): RedirectResponse {
        $user = $request->user();

        $updatePreferences($user, new UpdateUserPreferencesData(
            colorScheme: $user->color_scheme,
            theme: $user->theme,
            locale: $request->locale(),
            timezone: $user->timezone,
        ));

        return back();
    }
}
