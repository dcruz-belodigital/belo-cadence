<?php

declare(strict_types=1);

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Support\Documentation\DocumentLibrary;
use Illuminate\View\View;

/**
 * The changelog, written in `resources/docs/changelog.md`, one section per release.
 */
final class ChangelogController extends Controller
{
    public function __invoke(): View
    {
        return view('support.changelog', [
            'document' => DocumentLibrary::open('changelog'),
        ]);
    }
}
