<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Http\Controllers\Controller;
use App\Models\NotificationSchedule;
use App\Support\EmailTemplatePreview;
use Illuminate\View\View;

/**
 * The catalogue of email templates, each rendered as its reader receives it.
 *
 * Templates are the content of notification schedules, so seeing them needs the same
 * permission as seeing the schedules themselves rather than one of its own.
 */
final class EmailTemplateController extends Controller
{
    public function __invoke(EmailTemplatePreview $previews): View
    {
        $this->authorize('viewAny', NotificationSchedule::class);

        return view('cadence.email-templates', [
            'previews' => $previews->all(),
        ]);
    }
}
