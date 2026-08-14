<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('permission-protected pages', function (): void {
    it('refuses a user without the permission', function (string $routeName, PermissionName $permission): void {
        actingAs(administratorWithout([$permission]))
            ->get(route($routeName))
            ->assertForbidden();
    })->with([
        'dashboard' => ['dashboard', PermissionName::DashboardView],
        'clients' => ['clients.index', PermissionName::ClientsViewAny],
        'client create' => ['clients.create', PermissionName::ClientsCreate],
        'client import' => ['clients.import.create', PermissionName::ClientsImport],
        'client export' => ['clients.export', PermissionName::ClientsExport],
        'upcoming' => ['cadence.upcoming', PermissionName::ClientNotificationsViewAny],
        'schedules' => ['cadence.schedules.index', PermissionName::ClientNotificationsViewAny],
        'schedule export' => ['cadence.schedules.export', PermissionName::ClientNotificationsExport],
        'deliveries' => ['cadence.deliveries.index', PermissionName::NotificationDeliveriesViewAny],
        'delivery export' => ['cadence.deliveries.export', PermissionName::NotificationDeliveriesExport],
        'users' => ['admin.users.index', PermissionName::UsersViewAny],
        'user create' => ['admin.users.create', PermissionName::UsersCreate],
        'user import' => ['admin.users.import.create', PermissionName::UsersImport],
        'user export' => ['admin.users.export', PermissionName::UsersExport],
        'roles' => ['admin.roles.index', PermissionName::RolesViewAny],
        'role create' => ['admin.roles.create', PermissionName::RolesCreate],
        'role export' => ['admin.roles.export', PermissionName::RolesExport],
        'audit log' => ['admin.audit-log.index', PermissionName::AuditLogViewAny],
        'audit export' => ['admin.audit-log.export', PermissionName::AuditLogExport],
    ]);

    /*
    | The settings page gathers two separately permissioned sections, so it is refused
    | only to somebody who may see neither, and each section is hidden on its own.
    */
    it('refuses the settings page to a user who may see neither section', function (): void {
        actingAs(administratorWithout([
            PermissionName::ApplicationSettingsView,
            PermissionName::DefaultClientNotificationsView,
        ]))->get(route('admin.settings.edit'))->assertForbidden();
    });

    it('shows the settings page with only the section the reader may see', function (): void {
        $withoutDefaults = actingAs(administratorWithout([PermissionName::DefaultClientNotificationsView]))
            ->get(route('admin.settings.edit'))
            ->assertOk();

        expect($withoutDefaults->getContent())
            ->toContain(__('settings.fields.application_name'))
            ->not->toContain(__('settings.defaults.add'));

        $withoutSettings = actingAs(administratorWithout([PermissionName::ApplicationSettingsView]))
            ->get(route('admin.settings.edit'))
            ->assertOk();

        expect($withoutSettings->getContent())
            ->toContain(__('settings.defaults.add'))
            ->not->toContain(__('settings.fields.application_name'));
    });

    it('allows a user holding the permission', function (string $routeName): void {
        actingAs(administrator())
            ->get(route($routeName))
            ->assertOk();
    })->with([
        'dashboard',
        'clients.index',
        'clients.create',
        'clients.import.create',
        'cadence.upcoming',
        'cadence.schedules.index',
        'cadence.deliveries.index',
        'admin.users.index',
        'admin.users.create',
        'admin.users.import.create',
        'admin.roles.index',
        'admin.roles.create',
        'admin.audit-log.index',
        'admin.settings.edit',
        'profile.edit',
        'notifications.index',
    ]);
});

describe('record pages', function (): void {
    it('refuses viewing a client without the view permission', function (): void {
        $client = Client::factory()->create();

        actingAs(administratorWithout([PermissionName::ClientsView]))
            ->get(route('clients.show', $client))
            ->assertForbidden();
    });

    it('refuses viewing a schedule without the view permission', function (): void {
        $schedule = ClientNotificationSchedule::factory()->create();

        actingAs(administratorWithout([PermissionName::ClientNotificationsView]))
            ->get(route('cadence.schedules.show', $schedule))
            ->assertForbidden();
    });

    it('refuses viewing a delivery without the view permission', function (): void {
        $delivery = ClientNotificationDelivery::factory()->create();

        actingAs(administratorWithout([PermissionName::NotificationDeliveriesView]))
            ->get(route('cadence.deliveries.show', $delivery))
            ->assertForbidden();
    });

    it('refuses viewing a user without the view permission', function (): void {
        $user = User::factory()->create();

        actingAs(administratorWithout([PermissionName::UsersView]))
            ->get(route('admin.users.show', $user))
            ->assertForbidden();
    });

    it('refuses viewing a role without the view permission', function (): void {
        $role = Role::factory()->create();

        actingAs(administratorWithout([PermissionName::RolesView]))
            ->get(route('admin.roles.show', $role))
            ->assertForbidden();
    });
});

describe('imports', function (): void {
    it('refuses a client import when the user could not create clients by hand', function (): void {
        actingAs(administratorWithout([PermissionName::ClientsCreate]))
            ->get(route('clients.import.create'))
            ->assertForbidden();
    });

    it('refuses a client import when the user could not update clients by hand', function (): void {
        actingAs(administratorWithout([PermissionName::ClientsUpdate]))
            ->post(route('clients.import.store'))
            ->assertForbidden();
    });

    it('refuses a user import when the user could not create users by hand', function (): void {
        actingAs(administratorWithout([PermissionName::UsersCreate]))
            ->get(route('admin.users.import.create'))
            ->assertForbidden();
    });
});

describe('navigation', function (): void {
    it('hides sections a user cannot reach', function (): void {
        $coordinator = userWithPermissions([
            PermissionName::DashboardView,
            PermissionName::ClientsViewAny,
        ]);

        actingAs($coordinator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('navigation.clients'))
            ->assertDontSee(__('navigation.users'))
            ->assertDontSee(__('navigation.roles'))
            ->assertDontSee(__('navigation.audit_log'))
            ->assertDontSee(__('navigation.settings'));
    });

    it('shows administration to a user who can reach it', function (): void {
        actingAs(administrator())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('navigation.sections.administration'))
            ->assertSee(__('navigation.users'))
            ->assertSee(__('navigation.audit_log'));
    });

    it('hides the cadence section from a user without any cadence permission', function (): void {
        $user = userWithPermissions([PermissionName::DashboardView]);

        actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('navigation.upcoming'))
            ->assertDontSee(__('navigation.schedules'))
            ->assertDontSee(__('navigation.deliveries'));
    });
});
