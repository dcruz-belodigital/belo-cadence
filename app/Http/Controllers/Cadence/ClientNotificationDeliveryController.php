<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Data\ClientNotifications\ClientNotificationDeliveryFilters;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientNotificationDelivery;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Delivery history is read-only: there is deliberately no create, update or delete.
 */
final class ClientNotificationDeliveryController extends Controller
{
    public function index(Request $request, ViewerTimezone $viewerTimezone): View
    {
        $this->authorize('viewAny', ClientNotificationDelivery::class);

        $filters = ClientNotificationDeliveryFilters::fromRequest($request, $viewerTimezone->current());

        return view('cadence.deliveries.index', [
            'filters' => $filters,
            'deliveries' => ClientNotificationDelivery::query()
                ->filtered($filters)
                ->with('client')
                ->paginate(20)
                ->withQueryString(),
            'clients' => Client::query()->withTrashed()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function show(ClientNotificationDelivery $delivery): View
    {
        $this->authorize('view', $delivery);

        $delivery->load(['client', 'schedule']);

        return view('cadence.deliveries.show', ['delivery' => $delivery]);
    }
}
