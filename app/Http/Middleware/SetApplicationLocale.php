<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApplicationSettings;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders each request in the signed-in user's locale, or the application default.
 */
final class SetApplicationLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        App::setLocale($user instanceof User
            ? $user->locale->value
            : ApplicationSettings::current()->default_locale->value);

        return $next($request);
    }
}
