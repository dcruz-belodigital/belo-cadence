<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Data\Notifications\NotificationScheduleFilters;
use App\Http\Controllers\Controller;
use App\Models\NotificationSchedule;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What client communication is coming next, nearest first.
 */
final class UpcomingNotificationController extends Controller
{
    public function __invoke(Request $request, ViewerTimezone $viewerTimezone): View
    {
        $this->authorize('viewAny', NotificationSchedule::class);

        $filters = NotificationScheduleFilters::fromRequest(
            $request,
            $viewerTimezone->current(),
            scheduledOnly: true,
        );

        return view('cadence.upcoming', [
            'filters' => $filters,
            'schedules' => NotificationSchedule::query()
                ->filtered($filters)
                ->with('client')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
