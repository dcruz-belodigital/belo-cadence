<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Users\ActivateUserAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class ActivateUserController extends Controller
{
    public function __invoke(User $user, ActivateUserAction $activateUser): RedirectResponse
    {
        $this->authorize('activate', $user);

        $activateUser($user);

        return back()->with('success', __('users.flash.activated', ['name' => $user->name]));
    }
}
