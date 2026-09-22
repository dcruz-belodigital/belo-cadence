<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Notifications\CreateNotificationScheduleAction;
use App\Actions\Notifications\DeleteNotificationScheduleAction;
use App\Actions\Notifications\UpdateNotificationScheduleAction;
use App\Data\Notifications\NotificationScheduleFilters;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\StoreNotificationScheduleRequest;
use App\Http\Requests\Notifications\UpdateNotificationScheduleRequest;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\NotificationSchedule;
use App\Support\EmailTemplatePreview;
use App\Support\TemplateSlots;
use App\Support\ViewerTimezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class NotificationScheduleController extends Controller
{
    public function index(Request $request, ViewerTimezone $viewerTimezone): View
    {
        $this->authorize('viewAny', NotificationSchedule::class);

        $filters = NotificationScheduleFilters::fromRequest($request, $viewerTimezone->current());

        return view('cadence.schedules.index', [
            'filters' => $filters,
            'schedules' => NotificationSchedule::query()
                ->filtered($filters)
                ->with('client')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /**
     * The general form, where the target is still to be chosen.
     */
    public function create(
        ViewerTimezone $viewerTimezone,
        EmailTemplatePreview $templatePreviews,
    ): View {
        $this->authorize('create', NotificationSchedule::class);

        return view('cadence.schedules.create', [
            'client' => null,
            'clients' => $this->emailableClients(),
            'viewerTimezone' => $viewerTimezone->current()->value,
            'slotOptions' => TemplateSlots::formOptions(ClientAttribute::query()->active()->get()),
            'templatePreviews' => $templatePreviews->all(),
        ]);
    }

    /**
     * The same form reached through one client, which fixes the target to that client.
     */
    public function createForClient(
        Client $client,
        ViewerTimezone $viewerTimezone,
        EmailTemplatePreview $templatePreviews,
    ): View {
        $this->authorize('create', NotificationSchedule::class);

        return view('cadence.schedules.create', [
            'client' => $client,
            'clients' => $this->emailableClients(),
            'viewerTimezone' => $viewerTimezone->current()->value,
            'slotOptions' => TemplateSlots::formOptions(ClientAttribute::query()->active()->get()),
            'templatePreviews' => $templatePreviews->all(),
        ]);
    }

    public function store(
        StoreNotificationScheduleRequest $request,
        CreateNotificationScheduleAction $createSchedule,
    ): RedirectResponse {
        return $this->createSchedule($request, $createSchedule);
    }

    /**
     * The client-scoped route. It exists separately so `{client}` is resolved to the
     * model — implicit binding reads the action's signature — which is what lets the
     * form request know the target is already decided.
     */
    public function storeForClient(
        StoreNotificationScheduleRequest $request,
        Client $client,
        CreateNotificationScheduleAction $createSchedule,
    ): RedirectResponse {
        return $this->createSchedule($request, $createSchedule);
    }

    public function show(Request $request, NotificationSchedule $schedule): View
    {
        $this->authorize('view', $schedule);

        $schedule->load('client');

        return view('cadence.schedules.show', [
            'schedule' => $schedule,
            // Retired attributes are included, so a schedule still names what it attaches.
            'attachmentLabels' => TemplateSlots::attachmentLabels(
                $schedule->attachment_bindings,
                ClientAttribute::query()->ordered()->get(),
            ),
            'deliveries' => $request->user()->can(PermissionName::NotificationDeliveriesViewAny->value)
                ? $schedule->deliveries()->latest('scheduled_for')->limit(20)->get()
                : null,
        ]);
    }

    public function edit(
        NotificationSchedule $schedule,
        ViewerTimezone $viewerTimezone,
        EmailTemplatePreview $templatePreviews,
    ): View {
        $this->authorize('update', $schedule);

        $schedule->load('client');

        return view('cadence.schedules.edit', [
            'schedule' => $schedule,
            'clients' => $this->emailableClients(),
            'viewerTimezone' => $viewerTimezone->current()->value,
            'slotOptions' => TemplateSlots::formOptions(ClientAttribute::query()->active()->get()),
            'startsAtInputValue' => $viewerTimezone->toInputValue($schedule->starts_at),
            'templatePreviews' => $templatePreviews->all(),
        ]);
    }

    public function update(
        UpdateNotificationScheduleRequest $request,
        NotificationSchedule $schedule,
        UpdateNotificationScheduleAction $updateSchedule,
    ): RedirectResponse {
        $this->authorize('update', $schedule);

        $updateSchedule($schedule, $request->toData());

        return redirect()
            ->route('cadence.schedules.show', $schedule)
            ->with('success', __('cadence.flash.updated'));
    }

    public function destroy(
        NotificationSchedule $schedule,
        DeleteNotificationScheduleAction $deleteSchedule,
    ): RedirectResponse {
        $this->authorize('delete', $schedule);

        $client = $schedule->client;

        $deleteSchedule($schedule);

        // A list schedule has no client page to go back to.
        return $client instanceof Client
            ? redirect()->route('clients.show', $client)->with('success', __('cadence.flash.deleted'))
            : redirect()->route('cadence.schedules.index')->with('success', __('cadence.flash.deleted'));
    }

    private function createSchedule(
        StoreNotificationScheduleRequest $request,
        CreateNotificationScheduleAction $createSchedule,
    ): RedirectResponse {
        $this->authorize('create', NotificationSchedule::class);

        $schedule = $createSchedule($request->toData());

        $flash = __('cadence.flash.created', ['template' => $schedule->template->label()]);

        return $schedule->client_id === null
            ? redirect()->route('cadence.schedules.show', $schedule)->with('success', $flash)
            : redirect()->route('clients.show', $schedule->client_id)->with('success', $flash);
    }

    /**
     * The clients a schedule may be created for: only those that can actually be emailed.
     *
     * @return Collection<int, string>
     */
    private function emailableClients(): Collection
    {
        return Client::query()->active()->orderBy('name')->pluck('name', 'id');
    }
}
