<?php

declare(strict_types=1);

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Support\Documentation\DocumentLibrary;
use Illuminate\View\View;

/**
 * The user guide, written in `resources/docs/user-guide.md`.
 *
 * Every signed-in person can read it: knowing how the application works is not something
 * that needs a permission.
 */
final class UserGuideController extends Controller
{
    public function __invoke(): View
    {
        return view('support.user-guide', [
            'document' => DocumentLibrary::open('user-guide'),
        ]);
    }
}
