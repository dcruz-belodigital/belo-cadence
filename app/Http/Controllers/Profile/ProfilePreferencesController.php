<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Users\UpdateUserPreferencesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePreferencesRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Preferences are always applied to the signed-in user, so one person can never change
 * another person's display settings here.
 */
final class ProfilePreferencesController extends Controller
{
    public function update(
        UpdatePreferencesRequest $request,
        UpdateUserPreferencesAction $updatePreferences,
    ): RedirectResponse {
        $updatePreferences($request->user(), $request->toData());

        return back()->with('success', __('profile.flash.preferences_updated'));
    }
}
