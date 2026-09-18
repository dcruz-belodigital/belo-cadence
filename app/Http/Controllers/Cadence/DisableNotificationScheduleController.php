<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Notifications\DisableNotificationScheduleAction;
use App\Http\Controllers\Controller;
use App\Models\NotificationSchedule;
use Illuminate\Http\RedirectResponse;

final class DisableNotificationScheduleController extends Controller
{
    public function __invoke(
        NotificationSchedule $schedule,
        DisableNotificationScheduleAction $disableSchedule,
    ): RedirectResponse {
        $this->authorize('update', $schedule);

        $disableSchedule($schedule);

        return back()->with('success', __('cadence.flash.disabled'));
    }
}
