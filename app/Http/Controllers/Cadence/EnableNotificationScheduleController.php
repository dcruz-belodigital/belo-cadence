<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Notifications\EnableNotificationScheduleAction;
use App\Http\Controllers\Controller;
use App\Models\NotificationSchedule;
use Illuminate\Http\RedirectResponse;

final class EnableNotificationScheduleController extends Controller
{
    public function __invoke(
        NotificationSchedule $schedule,
        EnableNotificationScheduleAction $enableSchedule,
    ): RedirectResponse {
        $this->authorize('update', $schedule);

        $enableSchedule($schedule);

        return back()->with('success', __('cadence.flash.enabled'));
    }
}
