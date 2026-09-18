<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeValue;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

/**
 * @return list<list<string>>
 */
function csvRows(TestResponse $response): array
{
    $content = ltrim($response->streamedContent(), "\xEF\xBB\xBF");

    $rows = [];

    foreach (preg_split('/\r\n|\n|\r/', trim($content)) as $line) {
        if ($line !== '') {
            $rows[] = str_getcsv($line, escape: '');
        }
    }

    return $rows;
}

describe('client exports', function (): void {
    it('appends a column per active attribute, in the order somebody chose', function (): void {
        $owner = ClientAttribute::factory()->create(['name' => 'Account owner', 'key' => 'account_owner', 'position' => 2]);
        $tier = ClientAttribute::factory()->select()->create(['name' => 'Tier', 'key' => 'tier', 'position' => 1]);
        ClientAttribute::factory()->inactive()->create(['name' => 'Retired field', 'key' => 'retired_field', 'position' => 3]);

        $client = Client::factory()->create(['name' => 'Northwind Studio']);
        ClientAttributeValue::factory()->for($client)->of($owner, 'Ana Costa')->create();
        ClientAttributeValue::factory()->for($client)->of($tier, 'Gold')->create();

        $raw = csvRows(actingAs(administrator())->get(route('clients.export', ['mode' => 'raw']))->assertOk());

        // The columns that were always there keep their places, so an existing reader still works.
        expect($raw[0])->toBe([
            'id', 'name', 'email', 'status', 'notes', 'created_at', 'updated_at', 'deleted_at',
            'attribute_tier', 'attribute_account_owner',
        ])->and($raw[1][8])->toBe('Gold')
            ->and($raw[1][9])->toBe('Ana Costa');

        $table = csvRows(actingAs(administrator())->get(route('clients.export', ['mode' => 'table']))->assertOk());

        expect($table[0])->toBe([
            __('clients.columns.name'), __('clients.columns.email'), __('clients.columns.status'),
            __('clients.columns.schedules'), __('clients.columns.created'),
            'Tier', 'Account owner',
        ])->and($table[0])->not->toContain('Retired field');
    });

    it('writes a repeater as json for a machine and as words for a person', function (): void {
        $contacts = ClientAttribute::factory()->repeater()->create(['name' => 'Contacts', 'key' => 'contacts', 'position' => 1]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($contacts, [
            ['name' => 'Ana', 'extension' => 22],
        ])->create();

        $raw = csvRows(actingAs(administrator())->get(route('clients.export', ['mode' => 'raw']))->assertOk());
        $table = csvRows(actingAs(administrator())->get(route('clients.export', ['mode' => 'table']))->assertOk());

        expect($raw[1][8])->toBe('[{"name":"Ana","extension":22}]')
            ->and($table[1][5])->toBe('Name: Ana, Extension: 22');
    });

    it('writes the raw columns with raw values and iso dates', function (): void {
        $client = Client::factory()->create([
            'name' => 'Northwind Studio',
            'email' => 'hello@northwind.test',
            'status' => ClientStatus::Inactive,
        ]);

        $rows = csvRows(actingAs(administrator())
            ->get(route('clients.export', ['mode' => 'raw']))
            ->assertOk());

        expect($rows[0])->toBe(['id', 'name', 'email', 'status', 'notes', 'created_at', 'updated_at', 'deleted_at'])
            ->and($rows[1][1])->toBe('Northwind Studio')
            ->and($rows[1][2])->toBe('hello@northwind.test')
            ->and($rows[1][3])->toBe('inactive')
            ->and($rows[1][5])->toBe($client->created_at->toIso8601String());
    });

    it('writes the table columns with readable values', function (): void {
        Client::factory()->create(['name' => 'Northwind Studio', 'status' => ClientStatus::Active]);

        $rows = csvRows(actingAs(administrator())
            ->get(route('clients.export', ['mode' => 'table']))
            ->assertOk());

        expect($rows[0])->toBe([
            __('clients.columns.name'),
            __('clients.columns.email'),
            __('clients.columns.status'),
            __('clients.columns.schedules'),
            __('clients.columns.created'),
        ])->and($rows[1][2])->toBe(ClientStatus::Active->label());
    });

    it('exports exactly the rows the current filters show', function (): void {
        Client::factory()->create(['name' => 'Northwind Studio']);
        Client::factory()->create(['name' => 'Harbour & Pine']);

        $rows = csvRows(actingAs(administrator())
            ->get(route('clients.export', ['mode' => 'table', 'search' => 'northwind']))
            ->assertOk());

        expect($rows)->toHaveCount(2)
            ->and($rows[1][0])->toBe('Northwind Studio');
    });

    it('respects the current sorting', function (): void {
        Client::factory()->create(['name' => 'Alpha Studio']);
        Client::factory()->create(['name' => 'Zeta Studio']);

        $rows = csvRows(actingAs(administrator())
            ->get(route('clients.export', ['mode' => 'table', 'sort' => 'name', 'direction' => 'desc']))
            ->assertOk());

        expect($rows[1][0])->toBe('Zeta Studio')
            ->and($rows[2][0])->toBe('Alpha Studio');
    });

    it('defaults to the table export when no mode is given', function (): void {
        Client::factory()->create();

        $rows = csvRows(actingAs(administrator())->get(route('clients.export'))->assertOk());

        expect($rows[0][0])->toBe(__('clients.columns.name'));
    });

    it('refuses a user without the export permission', function (): void {
        actingAs(administratorWithout([PermissionName::ClientsExport]))
            ->get(route('clients.export'))
            ->assertForbidden();
    });
});

describe('schedule exports', function (): void {
    it('writes raw and table shapes', function (): void {
        $schedule = NotificationSchedule::factory()
            ->for(Client::factory()->create(['name' => 'Northwind Studio']))
            ->create();

        $administrator = administrator();

        $raw = csvRows(actingAs($administrator)
            ->get(route('cadence.schedules.export', ['mode' => 'raw']))
            ->assertOk());

        expect($raw[0])->toContain('client_id', 'name', 'recipients', 'template', 'frequency', 'next_send_at')
            ->and($raw[1][array_search('template', $raw[0], true)])->toBe($schedule->template->value);

        $table = csvRows(actingAs($administrator)
            ->get(route('cadence.schedules.export', ['mode' => 'table']))
            ->assertOk());

        expect($table[0][0])->toBe(__('cadence.columns.name'))
            ->and($table[1][0])->toBe('Northwind Studio');
    });

    it('exports only scheduled rows when it comes from the upcoming overview', function (): void {
        NotificationSchedule::factory()
            ->for(Client::factory()->create(['name' => 'Scheduled Client']))
            ->create();

        NotificationSchedule::factory()
            ->for(Client::factory()->create(['name' => 'Disabled Client']))
            ->disabled()
            ->create();

        $rows = csvRows(actingAs(administrator())
            ->get(route('cadence.schedules.export', ['upcoming' => 1, 'mode' => 'table']))
            ->assertOk());

        expect($rows)->toHaveCount(2)
            ->and($rows[1][0])->toBe('Scheduled Client');
    });

    it('offers an export link from the upcoming overview that keeps working', function (): void {
        NotificationSchedule::factory()->for(Client::factory())->create();

        $response = actingAs(administrator())
            ->get(route('cadence.upcoming', ['range' => 'next_30_days']))
            ->assertOk();

        expect($response->getContent())
            ->toContain('upcoming=1&amp;mode=table')
            ->toContain('range=next_30_days');
    });

    it('refuses a user without the export permission', function (): void {
        actingAs(administratorWithout([PermissionName::NotificationsExport]))
            ->get(route('cadence.schedules.export'))
            ->assertForbidden();
    });
});

describe('delivery exports', function (): void {
    it('writes raw and table shapes without the message body', function (): void {
        NotificationDelivery::factory()->failed()->create(['subject' => 'Annual reminder']);

        $administrator = administrator();

        $raw = csvRows(actingAs($administrator)
            ->get(route('cadence.deliveries.export', ['mode' => 'raw']))
            ->assertOk());

        expect($raw[0])->toContain('status', 'failure_message', 'scheduled_for', 'is_manual', 'triggered_by_user_id')
            ->and($raw[0])->not->toContain('body_html');

        $table = csvRows(actingAs($administrator)
            ->get(route('cadence.deliveries.export', ['mode' => 'table']))
            ->assertOk());

        expect($table[1])->toContain('Annual reminder');
    });

    it('names where each delivery came from', function (): void {
        NotificationDelivery::factory()->manual()->create(['subject' => 'Sent by hand']);
        NotificationDelivery::factory()->create(['subject' => 'Sent by the scheduler']);

        $rows = csvRows(actingAs(administrator())
            ->get(route('cadence.deliveries.export', ['mode' => 'table']))
            ->assertOk());

        expect($rows[0])->toContain(__('deliveries.columns.source'))
            ->and(collect($rows)->flatten()->all())
            ->toContain(__('enums.notification_delivery_source.manual'))
            ->toContain(__('enums.notification_delivery_source.scheduled'));
    });

    it('exports only manual sends when history is filtered to them', function (): void {
        NotificationDelivery::factory()->manual()->create(['subject' => 'Sent by hand']);
        NotificationDelivery::factory()->create(['subject' => 'Sent by the scheduler']);

        $rows = csvRows(actingAs(administrator())
            ->get(route('cadence.deliveries.export', ['mode' => 'table', 'source' => 'manual']))
            ->assertOk());

        expect($rows)->toHaveCount(2)
            ->and($rows[1])->toContain('Sent by hand');
    });

    it('respects the status filter', function (): void {
        NotificationDelivery::factory()->failed()->create(['subject' => 'Failed one']);
        NotificationDelivery::factory()->sent()->create(['subject' => 'Sent one']);

        $rows = csvRows(actingAs(administrator())
            ->get(route('cadence.deliveries.export', ['mode' => 'table', 'status' => 'failed']))
            ->assertOk());

        expect($rows)->toHaveCount(2)
            ->and($rows[1])->toContain('Failed one');
    });
});

describe('user exports', function (): void {
    it('never includes the password', function (): void {
        User::factory()->create(['name' => 'Ada Demo']);

        $rows = csvRows(actingAs(administrator())
            ->get(route('admin.users.export', ['mode' => 'raw']))
            ->assertOk());

        expect($rows[0])->not->toContain('password')
            ->and(implode(',', $rows[0]))->not->toContain('password');
    });

    it('lists the roles a user holds', function (): void {
        $user = userWithPermissions([PermissionName::DashboardView], ['name' => 'Bruno Demo']);
        $roleName = $user->roles->first()->name;

        $rows = csvRows(actingAs(administrator())
            ->get(route('admin.users.export', ['mode' => 'raw']))
            ->assertOk());

        $brunoRow = collect($rows)->firstWhere(1, 'Bruno Demo');

        expect($brunoRow[4])->toBe($roleName);
    });

    it('refuses a user without the export permission', function (): void {
        actingAs(administratorWithout([PermissionName::UsersExport]))
            ->get(route('admin.users.export'))
            ->assertForbidden();
    });
});

describe('role exports', function (): void {
    it('writes the permissions each role grants', function (): void {
        $role = Role::factory()->create(['name' => 'Coordinator']);
        $role->givePermissionTo(Permission::findOrCreate(PermissionName::ClientsViewAny->value, 'web'));

        $rows = csvRows(actingAs(administrator())
            ->get(route('admin.roles.export', ['mode' => 'raw']))
            ->assertOk());

        $coordinatorRow = collect($rows)->firstWhere(1, 'Coordinator');

        expect($coordinatorRow[2])->toBe(PermissionName::ClientsViewAny->value);
    });

    it('refuses a user without the export permission', function (): void {
        actingAs(administratorWithout([PermissionName::RolesExport]))
            ->get(route('admin.roles.export'))
            ->assertForbidden();
    });
});

describe('audit exports', function (): void {
    it('writes the recorded values as json in the raw export', function (): void {
        Audit::factory()->create([
            'action' => AuditAction::ClientUpdated,
            'old_values' => ['name' => 'Old'],
            'new_values' => ['name' => 'New'],
        ]);

        $rows = csvRows(actingAs(administrator())
            ->get(route('admin.audit-log.export', ['mode' => 'raw']))
            ->assertOk());

        expect($rows[0])->toContain('old_values', 'new_values', 'metadata')
            ->and($rows[1])->toContain('{"name":"Old"}');
    });

    it('names the system as the actor in the table export', function (): void {
        Audit::factory()->bySystem()->create();

        $rows = csvRows(actingAs(administrator())
            ->get(route('admin.audit-log.export', ['mode' => 'table']))
            ->assertOk());

        expect($rows[1][1])->toBe(__('audit.system_actor'));
    });

    it('respects the date filter', function (): void {
        Audit::factory()->create(['created_at' => CarbonImmutable::parse('2026-05-10 09:00', 'UTC'), 'action' => AuditAction::ClientCreated]);
        Audit::factory()->create(['created_at' => CarbonImmutable::parse('2026-01-10 09:00', 'UTC'), 'action' => AuditAction::ClientArchived]);

        $rows = csvRows(actingAs(administrator())
            ->get(route('admin.audit-log.export', ['mode' => 'table', 'from' => '2026-05-01']))
            ->assertOk());

        expect($rows)->toHaveCount(2)
            ->and($rows[1][2])->toBe(AuditAction::ClientCreated->label());
    });

    it('refuses a user without the export permission', function (): void {
        actingAs(administratorWithout([PermissionName::AuditLogExport]))
            ->get(route('admin.audit-log.export'))
            ->assertForbidden();
    });
});
