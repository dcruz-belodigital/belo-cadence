<?php

declare(strict_types=1);

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientNotificationSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

describe('what gets recorded', function (): void {
    it('names the person who made the change', function (): void {
        $administrator = administrator(['name' => 'Ada Demo']);

        actingAs($administrator)->post(route('clients.store'), [
            'name' => 'Northwind Studio',
            'email' => 'hello@northwind.test',
            'status' => ClientStatus::Active->value,
        ]);

        $audit = Audit::query()->where('action', AuditAction::ClientCreated->value)->firstOrFail();

        expect($audit->user_id)->toBe($administrator->getKey())
            ->and($audit->user->name)->toBe('Ada Demo');
    });

    it('records work done by the scheduler as a system action', function (): void {
        Mail::fake();

        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->due()
            ->create();

        // No signed-in user: this is how the scheduler runs.
        app(RecordAuditAction::class)(
            new RecordAuditData(
                action: AuditAction::ClientsImported,
                metadata: ['created' => 1, 'updated' => 0],
            )
        );

        $audit = Audit::query()->where('action', AuditAction::ClientsImported->value)->firstOrFail();

        expect($audit->user_id)->toBeNull();

        actingAs(administrator())
            ->get(route('admin.audit-log.index'))
            ->assertOk()
            ->assertSee(__('audit.system_actor'));
    });

    it('keeps the values before and after a change', function (): void {
        $client = Client::factory()->create(['name' => 'Old Name']);

        actingAs(administrator())->put(route('clients.update', $client), [
            'name' => 'New Name',
            'email' => $client->email->value,
            'status' => $client->status->value,
        ]);

        $audit = Audit::query()->where('action', AuditAction::ClientUpdated->value)->firstOrFail();

        expect($audit->old_values['name'])->toBe('Old Name')
            ->and($audit->new_values['name'])->toBe('New Name')
            ->and($audit->auditable_type)->toBe('client')
            ->and($audit->auditable_id)->toBe($client->getKey());
    });

    it('does not record ordinary page views', function (): void {
        $administrator = administrator();

        actingAs($administrator)->get(route('clients.index'))->assertOk();
        actingAs($administrator)->get(route('dashboard'))->assertOk();

        expect(Audit::query()->count())->toBe(0);
    });
});

describe('reading the audit log', function (): void {
    it('lists entries with their actor and action', function (): void {
        Audit::factory()->create([
            'action' => AuditAction::ClientCreated,
            'user_id' => User::factory()->create(['name' => 'Ada Demo']),
        ]);

        actingAs(administrator())
            ->get(route('admin.audit-log.index'))
            ->assertOk()
            ->assertSee('Ada Demo')
            ->assertSee(AuditAction::ClientCreated->label());
    });

    it('filters by action', function (): void {
        // The actor names identify the rows: every action label also appears in the
        // filter dropdown.
        Audit::factory()->action(AuditAction::ClientCreated)
            ->create(['user_id' => User::factory()->create(['name' => 'Ada Demo'])]);

        Audit::factory()->action(AuditAction::RoleDeleted)
            ->create(['user_id' => User::factory()->create(['name' => 'Bruno Demo'])]);

        actingAs(administrator())
            ->get(route('admin.audit-log.index', ['action' => AuditAction::RoleDeleted->value]))
            ->assertOk()
            ->assertSee('Bruno Demo')
            ->assertDontSee('Ada Demo');
    });

    it('searches by actor', function (): void {
        Audit::factory()->create(['user_id' => User::factory()->create(['name' => 'Ada Demo'])]);
        Audit::factory()->create(['user_id' => User::factory()->create(['name' => 'Bruno Demo'])]);

        actingAs(administrator())
            ->get(route('admin.audit-log.index', ['search' => 'Ada']))
            ->assertOk()
            ->assertSee('Ada Demo')
            ->assertDontSee('Bruno Demo');
    });

    it('filters by date', function (): void {
        Audit::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-05-10 09:00', 'UTC'),
            'user_id' => User::factory()->create(['name' => 'Recent Actor']),
        ]);

        Audit::factory()->create([
            'created_at' => CarbonImmutable::parse('2026-01-10 09:00', 'UTC'),
            'user_id' => User::factory()->create(['name' => 'Older Actor']),
        ]);

        actingAs(administrator())
            ->get(route('admin.audit-log.index', ['from' => '2026-05-01', 'to' => '2026-05-31']))
            ->assertOk()
            ->assertSee('Recent Actor')
            ->assertDontSee('Older Actor');
    });

    it('opens a single entry with its recorded values', function (): void {
        $audit = Audit::factory()->create([
            'action' => AuditAction::ClientUpdated,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ]);

        actingAs(administrator())
            ->get(route('admin.audit-log.show', $audit))
            ->assertOk()
            ->assertSee('Old Name')
            ->assertSee('New Name');
    });

    it('says so when the record an entry refers to is gone', function (): void {
        $audit = Audit::factory()->create([
            'auditable_type' => 'client',
            'auditable_id' => 999_999,
        ]);

        actingAs(administrator())
            ->get(route('admin.audit-log.show', $audit))
            ->assertOk()
            ->assertSee(__('audit.show.record_missing'));
    });
});

describe('the audit log is read-only', function (): void {
    it('has no route for creating, changing, deleting or importing entries', function (): void {
        $audit = Audit::factory()->create();

        actingAs(administrator());

        post('/admin/audit-log')->assertStatus(405);
        put('/admin/audit-log/'.$audit->getKey())->assertStatus(405);
        delete('/admin/audit-log/'.$audit->getKey())->assertStatus(405);

        expect(Route::has('admin.audit-log.import'))->toBeFalse();
    });

    it('never authorises changing an entry, even for an administrator', function (): void {
        $audit = Audit::factory()->create();
        $administrator = administrator();

        expect($administrator->can('create', Audit::class))->toBeFalse()
            ->and($administrator->can('update', $audit))->toBeFalse()
            ->and($administrator->can('delete', $audit))->toBeFalse();
    });

    it('refuses the log to a user without the permission', function (): void {
        actingAs(administratorWithout([PermissionName::AuditLogViewAny]))
            ->get(route('admin.audit-log.index'))
            ->assertForbidden();
    });

    it('refuses a single entry to a user without the permission', function (): void {
        $audit = Audit::factory()->create();

        actingAs(administratorWithout([PermissionName::AuditLogView]))
            ->get(route('admin.audit-log.show', $audit))
            ->assertForbidden();
    });
});
