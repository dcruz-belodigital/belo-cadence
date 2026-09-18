<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\NotificationSchedule;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * A table row keeps its actions behind one trigger, and the ones that send email or
 * change what a schedule does next ask before they run. Neither shows up in a test that
 * only checks a page renders, and both are easy to undo while editing a view.
 */
describe('the row menu', function (): void {
    it('puts a row action behind one trigger', function (): void {
        $client = Client::factory()->create();
        NotificationSchedule::factory()->for($client)->count(2)->create();

        actingAs(administrator())
            ->get(route('cadence.schedules.index'))
            ->assertOk()
            ->assertSee(__('common.actions.more'))
            ->assertSee(__('common.actions.view'))
            ->assertSee(__('common.actions.edit'));
    });

    it('draws no trigger for a reader with nothing to do', function (): void {
        $client = Client::factory()->create();
        NotificationSchedule::factory()->for($client)->count(2)->create();

        actingAs(userWithPermissions([PermissionName::NotificationsViewAny]))
            ->get(route('cadence.schedules.index'))
            ->assertOk()
            ->assertDontSee(__('common.actions.more'));
    });
});

describe('actions that ask first', function (): void {
    it('asks before a schedule is sent by hand', function (): void {
        $client = Client::factory()->create();
        NotificationSchedule::factory()->for($client)->count(2)->create();

        actingAs(administrator())
            ->get(route('cadence.schedules.index'))
            ->assertOk()
            ->assertSee(__('cadence.send.title'))
            ->assertSee(__('cadence.send.message'));
    });

    it('asks before an enabled schedule is disabled', function (): void {
        $client = Client::factory()->create();
        NotificationSchedule::factory()->for($client)->count(2)->create();

        actingAs(administrator())
            ->get(route('cadence.schedules.index'))
            ->assertOk()
            ->assertSee(__('cadence.disable.title'))
            ->assertDontSee(__('cadence.enable.title'));
    });

    it('asks before a disabled schedule is enabled', function (): void {
        $client = Client::factory()->create();
        NotificationSchedule::factory()->for($client)->disabled()->count(2)->create();

        actingAs(administrator())
            ->get(route('cadence.schedules.index'))
            ->assertOk()
            ->assertSee(__('cadence.enable.title'))
            ->assertDontSee(__('cadence.disable.title'));
    });

    it('asks before an account is activated or deactivated', function (): void {
        $active = User::factory()->create();
        $inactive = User::factory()->inactive()->create();

        actingAs($administrator = administrator())
            ->get(route('admin.users.show', $active))
            ->assertOk()
            ->assertSee(__('users.deactivate.title'));

        actingAs($administrator)
            ->get(route('admin.users.show', $inactive))
            ->assertOk()
            ->assertSee(__('users.activate.title'));
    });
});
