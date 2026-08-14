<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\ClientNotifications\DisableClientNotificationScheduleAction;
use App\Http\Controllers\Controller;
use App\Models\ClientNotificationSchedule;
use Illuminate\Http\RedirectResponse;

final class DisableClientNotificationScheduleController extends Controller
{
    public function __invoke(
        ClientNotificationSchedule $schedule,
        DisableClientNotificationScheduleAction $disableSchedule,
    ): RedirectResponse {
        $this->authorize('update', $schedule);

        $disableSchedule($schedule);

        return back()->with('success', __('cadence.flash.disabled'));
    }
}
