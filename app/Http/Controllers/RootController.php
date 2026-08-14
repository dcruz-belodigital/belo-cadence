<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Belo Cadence has no public surface: the root URL only points people at the
 * dashboard or the sign-in page.
 */
final class RootController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }
}
