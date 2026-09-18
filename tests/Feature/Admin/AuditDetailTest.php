<?php

declare(strict_types=1);

use App\Models\ApplicationSettings;
use App\Models\Audit;
use App\Models\Client;
use App\Models\DefaultClientNotification;
use App\Models\NotificationSchedule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

use function Pest\Laravel\actingAs;

/**
 * An audit entry can point at any kind of record, and only some of those support soft
 * deletion. Each one has to open.
 */
it('opens an audit entry for every kind of record it can refer to', function (Model $record): void {
    $audit = Audit::factory()->forRecord($record)->create();

    actingAs(administrator())
        ->get(route('admin.audit-log.show', $audit))
        ->assertOk()
        ->assertSee($audit->auditableTypeLabel());
})->with([
    'client' => [fn (): Model => Client::factory()->create()],
    'archived client' => [fn (): Model => Client::factory()->archived()->create()],
    'schedule' => [fn (): Model => NotificationSchedule::factory()->for(Client::factory())->create()],
    'user' => [fn (): Model => User::factory()->create()],
    'role' => [fn (): Model => Role::factory()->create()],
    'application settings' => [function (): Model {
        $settings = ApplicationSettings::defaults();
        $settings->save();

        return $settings;
    }],
    'default notification' => [fn (): Model => DefaultClientNotification::factory()->create()],
]);

it('lists entries for every kind of record without failing', function (): void {
    Audit::factory()->forRecord(Client::factory()->create())->create();
    Audit::factory()->forRecord(User::factory()->create())->create();
    Audit::factory()->forRecord(Role::factory()->create())->create();
    Audit::factory()->bySystem()->create();

    actingAs(administrator())
        ->get(route('admin.audit-log.index'))
        ->assertOk()
        ->assertSee(__('audit.auditable_types.client'))
        ->assertSee(__('audit.auditable_types.user'))
        ->assertSee(__('audit.auditable_types.role'))
        ->assertSee(__('audit.system_actor'));
});

it('filters by the kind of record', function (): void {
    Audit::factory()->forRecord(Client::factory()->create())
        ->create(['user_id' => User::factory()->create(['name' => 'Client Actor'])]);

    Audit::factory()->forRecord(Role::factory()->create())
        ->create(['user_id' => User::factory()->create(['name' => 'Role Actor'])]);

    actingAs(administrator())
        ->get(route('admin.audit-log.index', ['type' => 'role']))
        ->assertOk()
        ->assertSee('Role Actor')
        ->assertDontSee('Client Actor');
});
