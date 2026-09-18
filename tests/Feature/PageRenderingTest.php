<?php

declare(strict_types=1);

use App\Enums\ColorScheme;
use App\Models\Audit;
use App\Models\Client;
use App\Models\DefaultClientNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Every page an authorised user can open, with real records behind it. This is the
 * safety net for view-level mistakes that unit-level tests cannot see.
 *
 * @return list<string>
 */
function everyPageUrl(): array
{
    $client = Client::factory()->withNotes()->create();
    $archivedClient = Client::factory()->archived()->create();
    $schedule = NotificationSchedule::factory()->for($client)->create();
    // A second schedule for the same client, because Eloquent only guards a result set
    // of more than one row against lazy loading — one row hides a missing eager load.
    NotificationSchedule::factory()->for($client)->create();
    $delivery = NotificationDelivery::factory()->forSchedule($schedule)->failed()->create();
    // A manual delivery has no schedule behind it, which its page has to survive.
    $manualDelivery = NotificationDelivery::factory()->manual()->create();
    // A schedule with no client at all, and one whose wording lives on the schedule.
    $listSchedule = NotificationSchedule::factory()->forRecipients('Weekly ops digest')->create();
    $blankSchedule = NotificationSchedule::factory()->forRecipients('Board note')->blank()->create();
    $listDelivery = NotificationDelivery::factory()->forRecipientList('Weekly ops digest')->create();
    $audit = Audit::factory()->forRecord($client)->create();
    $role = Role::factory()->create();
    $user = User::factory()->create();

    DefaultClientNotification::factory()->create();

    return [
        route('dashboard'),
        route('clients.index'),
        route('clients.index', ['archived' => 1, 'search' => 'a', 'status' => 'active', 'sort' => 'email', 'direction' => 'desc']),
        route('clients.create'),
        route('clients.show', $client),
        route('clients.edit', $client),
        route('clients.import.create'),
        route('cadence.email-templates'),
        route('support.user-guide'),
        route('support.changelog'),
        route('clients.schedules.create', $client),
        route('cadence.upcoming'),
        route('cadence.upcoming', ['range' => 'next_7_days', 'template' => 'monthly_reminder', 'frequency' => 'monthly', 'state' => 'enabled']),
        route('cadence.schedules.index'),
        route('cadence.schedules.index', ['sort' => 'client', 'direction' => 'desc']),
        route('cadence.schedules.show', $schedule),
        route('cadence.schedules.edit', $schedule),
        route('cadence.schedules.create'),
        route('cadence.schedules.show', $listSchedule),
        route('cadence.schedules.edit', $listSchedule),
        route('cadence.schedules.show', $blankSchedule),
        route('cadence.schedules.edit', $blankSchedule),
        route('cadence.schedules.index', ['target' => 'recipients']),
        route('cadence.deliveries.index'),
        route('cadence.deliveries.index', ['status' => 'failed', 'from' => '2020-01-01', 'to' => '2030-01-01', 'sort' => 'client']),
        route('cadence.deliveries.show', $delivery),
        route('cadence.deliveries.show', $manualDelivery),
        route('cadence.deliveries.show', $listDelivery),
        route('cadence.deliveries.index', ['source' => 'manual']),
        route('cadence.deliveries.send'),
        route('cadence.deliveries.send', ['client' => $client->getKey()]),
        route('admin.users.index'),
        route('admin.users.index', ['state' => 'active', 'search' => 'a']),
        route('admin.users.create'),
        route('admin.users.show', $user),
        route('admin.users.edit', $user),
        route('admin.users.import.create'),
        route('admin.roles.index'),
        route('admin.roles.create'),
        route('admin.roles.show', $role),
        route('admin.roles.edit', $role),
        route('admin.audit-log.index'),
        route('admin.audit-log.index', ['type' => 'client', 'from' => '2020-01-01']),
        route('admin.audit-log.show', $audit),
        route('admin.settings.edit'),
        route('admin.settings.edit'),
        route('profile.edit'),
        route('notifications.index'),
        // Archived clients are reachable through the filtered list.
        route('clients.index', ['archived' => 1]).'&page=1',
    ];
}

it('renders every page for an administrator', function (): void {
    $administrator = administrator();

    foreach (everyPageUrl() as $url) {
        actingAs($administrator)
            ->get($url)
            ->assertOk();
    }
});

it('renders every page for a reader who prefers dark mode', function (): void {
    $administrator = administrator(['color_scheme' => ColorScheme::Dark, 'timezone' => 'America/New_York']);

    foreach (everyPageUrl() as $url) {
        actingAs($administrator)
            ->get($url)
            ->assertOk()
            ->assertSee('const preference = "dark"', escape: false);
    }
});

it('renders the guest pages', function (): void {
    foreach ([route('login'), route('password.request'), route('password.reset', ['token' => 'a-token'])] as $url) {
        $this->get($url)->assertOk();
    }
});

it('renders a client with an archived client in its history', function (): void {
    $client = Client::factory()->create();
    $schedule = NotificationSchedule::factory()->for($client)->create();
    NotificationDelivery::factory()->forSchedule($schedule)->create();

    $administrator = administrator();

    actingAs($administrator)->delete(route('clients.destroy', $client));

    actingAs($administrator)->get(route('cadence.deliveries.index'))->assertOk();
    actingAs($administrator)->get(route('cadence.schedules.index'))->assertOk();
});
