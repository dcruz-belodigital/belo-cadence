<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Notifications\SendManualNotificationAction;
use App\Enums\NotificationDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\SendManualNotificationRequest;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Support\EmailTemplatePreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sending an email by hand, outside any schedule.
 *
 * It goes either to a client or to a list of addresses typed on the spot, which is what
 * makes this more than a shortcut for a schedule: a one-off notice needs no schedule to
 * exist first. The result is ordinary delivery history, flagged as manual and carrying
 * the name of whoever asked for it.
 */
final class ManualNotificationController extends Controller
{
    public function create(Request $request, EmailTemplatePreview $templatePreviews): View
    {
        $this->authorize('notifyAny', Client::class);

        return view('cadence.deliveries.send', [
            // Only clients that may actually be emailed are offered.
            'clients' => Client::query()->active()->orderBy('name')->pluck('name', 'id'),
            'selectedClientId' => $request->filled('client') ? $request->integer('client') : null,
            'templatePreviews' => $templatePreviews->all(),
        ]);
    }

    public function store(
        SendManualNotificationRequest $request,
        SendManualNotificationAction $sendManualNotification,
    ): RedirectResponse {
        $client = $request->client();

        if ($client instanceof Client) {
            $this->authorize('notify', $client);
        }

        $dispatch = $request->toDispatch();
        $deliveries = $sendManualNotification($dispatch);

        /*
        | The delivery page is the natural place to land, but sending and reading history
        | are separate permissions — and one send can now produce several deliveries, so
        | only a single one has a page of its own to go to.
        */
        $first = $deliveries->first();
        $canRead = $first instanceof NotificationDelivery && $request->user()->can('view', $first);

        $destination = $canRead && $deliveries->count() === 1
            ? redirect()->route('cadence.deliveries.show', $first)
            : ($canRead
                ? redirect()->route('cadence.deliveries.index')
                : redirect()->route('cadence.deliveries.send'));

        $failed = $deliveries->filter(
            fn (NotificationDelivery $delivery): bool => $delivery->status === NotificationDeliveryStatus::Failed
        )->count();

        if ($failed > 0) {
            return $destination->with('error', __('deliveries.flash.failed', [
                'target' => (string) $dispatch->targetName,
                'failed' => $failed,
                'total' => $deliveries->count(),
            ]));
        }

        return $destination->with('success', __('deliveries.flash.sent', [
            'target' => (string) $dispatch->targetName,
            'count' => $deliveries->count(),
        ]));
    }
}
