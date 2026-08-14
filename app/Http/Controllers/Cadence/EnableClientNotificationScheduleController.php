<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\ClientNotifications\EnableClientNotificationScheduleAction;
use App\Http\Controllers\Controller;
use App\Models\ClientNotificationSchedule;
use Illuminate\Http\RedirectResponse;

final class EnableClientNotificationScheduleController extends Controller
{
    public function __invoke(
        ClientNotificationSchedule $schedule,
        EnableClientNotificationScheduleAction $enableSchedule,
    ): RedirectResponse {
        $this->authorize('update', $schedule);

        $enableSchedule($schedule);

        return back()->with('success', __('cadence.flash.enabled'));
    }
}
