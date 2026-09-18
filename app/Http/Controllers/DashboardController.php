<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\PermissionName;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Support\ViewerTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The operational overview: what needs attention now.
 *
 * Day and month boundaries are read in the viewer's timezone, and each panel is only
 * queried when the viewer is allowed to see it.
 */
final class DashboardController extends Controller
{
    private const RECENT_LIMIT = 5;

    public function __invoke(Request $request, ViewerTimezone $viewerTimezone): View
    {
        // The dashboard is not a resource, so it is guarded by its permission directly
        // rather than by a policy.
        abort_unless($request->user()->can(PermissionName::DashboardView->value), 403);

        $timezone = $viewerTimezone->current()->toDateTimeZone();
        $now = CarbonImmutable::now($timezone);
        $endOfToday = $now->endOfDay()->setTimezone('UTC');
        $endOfWeek = $now->addDays(6)->endOfDay()->setTimezone('UTC');
        $startOfMonth = $now->startOfMonth()->setTimezone('UTC');

        $user = $request->user();
        $canSeeSchedules = $user->can(PermissionName::NotificationsViewAny->value);
        $canSeeDeliveries = $user->can(PermissionName::NotificationDeliveriesViewAny->value);

        return view('dashboard.index', [
            'dueToday' => $canSeeSchedules
                ? NotificationSchedule::query()->due($endOfToday)->count()
                : null,
            'dueThisWeek' => $canSeeSchedules
                ? NotificationSchedule::query()->due($endOfWeek)->count()
                : null,
            'sentThisMonth' => $canSeeDeliveries
                ? NotificationDelivery::query()->sent()->where('sent_at', '>=', $startOfMonth)->count()
                : null,
            'failedThisMonth' => $canSeeDeliveries
                ? NotificationDelivery::query()->failed()->where('attempted_at', '>=', $startOfMonth)->count()
                : null,
            'upcoming' => $canSeeSchedules
                ? NotificationSchedule::query()
                    ->where('is_enabled', true)
                    ->whereNotNull('next_send_at')
                    ->with('client')
                    ->orderBy('next_send_at')
                    ->limit(self::RECENT_LIMIT)
                    ->get()
                : null,
            'recentFailures' => $canSeeDeliveries
                ? NotificationDelivery::query()
                    ->failed()
                    ->with('client')
                    ->latest('attempted_at')
                    ->limit(self::RECENT_LIMIT)
                    ->get()
                : null,
            'recentDeliveries' => $canSeeDeliveries
                ? NotificationDelivery::query()
                    ->where('status', NotificationDeliveryStatus::Sent)
                    ->with('client')
                    ->latest('sent_at')
                    ->limit(self::RECENT_LIMIT)
                    ->get()
                : null,
        ]);
    }
}
