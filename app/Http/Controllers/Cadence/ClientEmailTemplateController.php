<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Http\Controllers\Controller;
use App\Models\ClientNotificationSchedule;
use App\Support\ClientEmailTemplatePreview;
use Illuminate\View\View;

/**
 * The catalogue of client email templates, each rendered as a client receives it.
 *
 * Templates are the content of notification schedules, so seeing them needs the same
 * permission as seeing the schedules themselves rather than one of its own.
 */
final class ClientEmailTemplateController extends Controller
{
    public function __invoke(ClientEmailTemplatePreview $previews): View
    {
        $this->authorize('viewAny', ClientNotificationSchedule::class);

        return view('cadence.email-templates', [
            'previews' => $previews->all(),
        ]);
    }
}
