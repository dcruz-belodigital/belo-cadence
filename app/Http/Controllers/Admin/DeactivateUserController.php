<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Users\DeactivateUserAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class DeactivateUserController extends Controller
{
    public function __invoke(User $user, DeactivateUserAction $deactivateUser): RedirectResponse
    {
        // The policy refuses to deactivate yourself, or the last person able to
        // administer access.
        $this->authorize('deactivate', $user);

        $deactivateUser($user);

        return back()->with('success', __('users.flash.deactivated', ['name' => $user->name]));
    }
}
