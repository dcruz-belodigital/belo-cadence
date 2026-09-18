<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Data\Notifications\NotificationDeliveryFilters;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Delivery history is read-only: there is deliberately no create, update or delete.
 */
final class NotificationDeliveryController extends Controller
{
    public function index(Request $request, ViewerTimezone $viewerTimezone): View
    {
        $this->authorize('viewAny', NotificationDelivery::class);

        $filters = NotificationDeliveryFilters::fromRequest($request, $viewerTimezone->current());

        return view('cadence.deliveries.index', [
            'filters' => $filters,
            'viewerTimezone' => $viewerTimezone->current()->value,
            'deliveries' => NotificationDelivery::query()
                ->filtered($filters)
                ->with('client')
                ->paginate(20)
                ->withQueryString(),
            'clients' => Client::query()->withTrashed()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function show(NotificationDelivery $delivery): View
    {
        $this->authorize('view', $delivery);

        $delivery->load(['client', 'schedule', 'triggeredBy']);

        return view('cadence.deliveries.show', ['delivery' => $delivery]);
    }
}
