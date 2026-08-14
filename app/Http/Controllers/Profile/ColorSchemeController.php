<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Users\UpdateUserPreferencesAction;
use App\Data\Users\UpdateUserPreferencesData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateColorSchemeRequest;
use Illuminate\Http\RedirectResponse;

/**
 * The theme switcher in the top bar. It writes the same preference the profile page
 * does, leaving the person's other preferences untouched.
 */
final class ColorSchemeController extends Controller
{
    public function update(
        UpdateColorSchemeRequest $request,
        UpdateUserPreferencesAction $updatePreferences,
    ): RedirectResponse {
        $user = $request->user();

        $updatePreferences($user, new UpdateUserPreferencesData(
            colorScheme: $request->colorScheme(),
            theme: $user->theme,
            locale: $user->locale,
            timezone: $user->timezone,
        ));

        return back();
    }
}
