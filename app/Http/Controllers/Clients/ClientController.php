<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\ArchiveClientAction;
use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\Data\Clients\ClientFilters;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use App\Models\DefaultClientNotification;
use App\Support\ViewerTimezone;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $filters = ClientFilters::fromRequest($request);

        return view('clients.index', [
            'filters' => $filters,
            'clients' => Client::query()
                ->filtered($filters)
                ->withCount('notificationSchedules')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(ViewerTimezone $viewerTimezone): View
    {
        $this->authorize('create', Client::class);

        return view('clients.create', [
            'defaultNotifications' => DefaultClientNotification::query()->orderBy('id')->get(),
            'viewerTimezone' => $viewerTimezone->current()->value,
        ]);
    }

    public function store(StoreClientRequest $request, CreateClientAction $createClient): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $client = $createClient($request->toData());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', __('clients.flash.created', ['name' => $client->name]));
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorize('view', $client);

        // Each schedule carries its client as well, even on the client's own page:
        // `NotificationSchedulePolicy::send()` reads it to decide whether the send
        // action may be offered, and lazy loading is prevented outside production.
        $client->load(['notificationSchedules' => fn (Builder $schedules): Builder => $schedules
            ->with('client')
            ->orderByRaw('next_send_at is null')
            ->orderBy('next_send_at')]);

        return view('clients.show', [
            'client' => $client,
            'deliveries' => $request->user()->can(PermissionName::NotificationDeliveriesViewAny->value)
                ? $client->notificationDeliveries()->latest('scheduled_for')->limit(10)->get()
                : null,
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.edit', ['client' => $client]);
    }

    public function update(
        UpdateClientRequest $request,
        Client $client,
        UpdateClientAction $updateClient,
    ): RedirectResponse {
        $this->authorize('update', $client);

        $updateClient($client, $request->toData());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', __('clients.flash.updated', ['name' => $client->name]));
    }

    public function destroy(Client $client, ArchiveClientAction $archiveClient): RedirectResponse
    {
        $this->authorize('delete', $client);

        $archiveClient($client);

        return redirect()
            ->route('clients.index')
            ->with('success', __('clients.flash.archived', ['name' => $client->name]));
    }
}
