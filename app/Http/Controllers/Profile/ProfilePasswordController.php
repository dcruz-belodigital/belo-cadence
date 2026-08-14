<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Users\UpdateUserPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;

final class ProfilePasswordController extends Controller
{
    public function update(
        UpdatePasswordRequest $request,
        UpdateUserPasswordAction $updatePassword,
    ): RedirectResponse {
        $updatePassword($request->user(), $request->string('password')->toString());

        return back()->with('success', __('profile.flash.password_updated'));
    }
}
