<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\ClientNotifications\CreateClientNotificationScheduleAction;
use App\Actions\ClientNotifications\DeleteClientNotificationScheduleAction;
use App\Actions\ClientNotifications\UpdateClientNotificationScheduleAction;
use App\Data\ClientNotifications\ClientNotificationScheduleFilters;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientNotifications\StoreClientNotificationScheduleRequest;
use App\Http\Requests\ClientNotifications\UpdateClientNotificationScheduleRequest;
use App\Models\Client;
use App\Models\ClientNotificationSchedule;
use App\Support\ClientEmailTemplatePreview;
use App\Support\ViewerTimezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClientNotificationScheduleController extends Controller
{
    public function index(Request $request, ViewerTimezone $viewerTimezone): View
    {
        $this->authorize('viewAny', ClientNotificationSchedule::class);

        $filters = ClientNotificationScheduleFilters::fromRequest($request, $viewerTimezone->current());

        return view('cadence.schedules.index', [
            'filters' => $filters,
            'schedules' => ClientNotificationSchedule::query()
                ->filtered($filters)
                ->with('client')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(
        Client $client,
        ViewerTimezone $viewerTimezone,
        ClientEmailTemplatePreview $templatePreviews,
    ): View {
        $this->authorize('create', ClientNotificationSchedule::class);

        return view('cadence.schedules.create', [
            'client' => $client,
            'viewerTimezone' => $viewerTimezone->current()->value,
            'templatePreviews' => $templatePreviews->all(),
        ]);
    }

    public function store(
        StoreClientNotificationScheduleRequest $request,
        Client $client,
        CreateClientNotificationScheduleAction $createSchedule,
    ): RedirectResponse {
        $this->authorize('create', ClientNotificationSchedule::class);

        $schedule = $createSchedule($client, $request->toData());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', __('cadence.flash.created', ['template' => $schedule->template->label()]));
    }

    public function show(Request $request, ClientNotificationSchedule $schedule): View
    {
        $this->authorize('view', $schedule);

        $schedule->load('client');

        return view('cadence.schedules.show', [
            'schedule' => $schedule,
            'deliveries' => $request->user()->can(PermissionName::NotificationDeliveriesViewAny->value)
                ? $schedule->deliveries()->latest('scheduled_for')->limit(20)->get()
                : null,
        ]);
    }

    public function edit(
        ClientNotificationSchedule $schedule,
        ViewerTimezone $viewerTimezone,
        ClientEmailTemplatePreview $templatePreviews,
    ): View {
        $this->authorize('update', $schedule);

        $schedule->load('client');

        return view('cadence.schedules.edit', [
            'schedule' => $schedule,
            'viewerTimezone' => $viewerTimezone->current()->value,
            'startsAtInputValue' => $viewerTimezone->toInputValue($schedule->starts_at),
            'templatePreviews' => $templatePreviews->all(),
        ]);
    }

    public function update(
        UpdateClientNotificationScheduleRequest $request,
        ClientNotificationSchedule $schedule,
        UpdateClientNotificationScheduleAction $updateSchedule,
    ): RedirectResponse {
        $this->authorize('update', $schedule);

        $updateSchedule($schedule, $request->toData());

        return redirect()
            ->route('cadence.schedules.show', $schedule)
            ->with('success', __('cadence.flash.updated'));
    }

    public function destroy(
        ClientNotificationSchedule $schedule,
        DeleteClientNotificationScheduleAction $deleteSchedule,
    ): RedirectResponse {
        $this->authorize('delete', $schedule);

        $client = $schedule->client;

        $deleteSchedule($schedule);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', __('cadence.flash.deleted'));
    }
}
