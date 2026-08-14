<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Users\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Support\ActiveTheme;
use App\ValueObjects\TimezoneIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->load('roles');

        return view('profile.edit', [
            'user' => $user,
            'timezones' => TimezoneIdentifier::all(),
            'applicationTheme' => ActiveTheme::applicationDefault(),
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $updateProfile): RedirectResponse
    {
        $updateProfile($request->user(), $request->string('name')->toString());

        return back()->with('success', __('profile.flash.updated'));
    }
}
