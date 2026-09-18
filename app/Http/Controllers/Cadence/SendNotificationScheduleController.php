<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Notifications\SendManualNotificationAction;
use App\Data\Notifications\NotificationDispatch;
use App\Enums\NotificationDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\NotificationSchedule;
use Illuminate\Http\RedirectResponse;

/**
 * Sends a schedule's own message now, to its own recipients.
 *
 * The recurrence is untouched: no anchor moves and no occurrence is used up. What comes
 * out is an ordinary manual delivery, which is what makes this different from the
 * scheduler reaching the same schedule an hour later.
 */
final class SendNotificationScheduleController extends Controller
{
    public function __invoke(
        NotificationSchedule $schedule,
        SendManualNotificationAction $sendManualNotification,
    ): RedirectResponse {
        $this->authorize('send', $schedule);

        $schedule->load('client');

        $deliveries = $sendManualNotification(NotificationDispatch::forSchedule($schedule));

        $failed = $deliveries->filter(
            fn ($delivery): bool => $delivery->status === NotificationDeliveryStatus::Failed
        )->count();

        if ($deliveries->isEmpty()) {
            return back()->with('error', __('cadence.flash.send_no_recipients'));
        }

        return $failed > 0
            ? back()->with('error', __('cadence.flash.sent_with_failures', [
                'failed' => $failed,
                'total' => $deliveries->count(),
            ]))
            : back()->with('success', __('cadence.flash.sent', ['count' => $deliveries->count()]));
    }
}
