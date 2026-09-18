<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Client;
use App\Models\DefaultClientNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

describe('listing clients', function (): void {
    it('shows clients with their schedule count', function (): void {
        $client = Client::factory()->create(['name' => 'Northwind Studio']);
        NotificationSchedule::factory()->for($client)->count(2)->create();

        actingAs(administrator())
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Northwind Studio')
            ->assertSee($client->email->value);
    });

    it('searches by name and email', function (): void {
        Client::factory()->create(['name' => 'Northwind Studio', 'email' => 'hello@northwind.test']);
        Client::factory()->create(['name' => 'Harbour & Pine', 'email' => 'accounts@harbour.test']);

        actingAs(administrator())
            ->get(route('clients.index', ['search' => 'northwind']))
            ->assertOk()
            ->assertSee('Northwind Studio')
            ->assertDontSee('accounts@harbour.test');
    });

    it('filters by status', function (): void {
        Client::factory()->active()->create(['name' => 'Active Client']);
        Client::factory()->inactive()->create(['name' => 'Paused Client']);

        actingAs(administrator())
            ->get(route('clients.index', ['status' => ClientStatus::Inactive->value]))
            ->assertOk()
            ->assertSee('Paused Client')
            ->assertDontSee('Active Client');
    });

    it('hides archived clients unless they are asked for', function (): void {
        Client::factory()->archived()->create(['name' => 'Copper Lane Archive']);

        $administrator = administrator();

        actingAs($administrator)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertDontSee('Copper Lane Archive');

        actingAs($administrator)
            ->get(route('clients.index', ['archived' => 1]))
            ->assertOk()
            ->assertSee('Copper Lane Archive');
    });

    it('sorts by a whitelisted column and ignores anything else', function (): void {
        Client::factory()->create(['name' => 'Alpha']);
        Client::factory()->create(['name' => 'Beta']);

        actingAs(administrator())
            ->get(route('clients.index', ['sort' => 'name', 'direction' => 'desc']))
            ->assertOk();

        actingAs(administrator())
            ->get(route('clients.index', ['sort' => 'password', 'direction' => 'sideways']))
            ->assertOk();
    });
});

describe('creating a client', function (): void {
    it('creates a client and records an audit entry', function (): void {
        actingAs(administrator())
            ->post(route('clients.store'), [
                'name' => 'Northwind Studio',
                'email' => 'Hello@Northwind.test',
                'status' => ClientStatus::Active->value,
                'notes' => 'Prefers Mondays.',
            ])
            ->assertRedirect();

        $client = Client::query()->firstOrFail();

        expect($client->name)->toBe('Northwind Studio')
            ->and($client->email->value)->toBe('hello@northwind.test')
            ->and($client->status)->toBe(ClientStatus::Active)
            ->and($client->notes)->toBe('Prefers Mondays.');

        assertDatabaseHas('audits', [
            'action' => AuditAction::ClientCreated->value,
            'auditable_type' => 'client',
            'auditable_id' => $client->getKey(),
        ]);
    });

    it('validates the submitted data', function (array $payload, string $field): void {
        actingAs(administrator())
            ->post(route('clients.store'), $payload)
            ->assertSessionHasErrors($field);
    })->with([
        'missing name' => [['email' => 'a@b.test', 'status' => 'active'], 'name'],
        'missing email' => [['name' => 'A', 'status' => 'active'], 'email'],
        'invalid email' => [['name' => 'A', 'email' => 'not-an-email', 'status' => 'active'], 'email'],
        'invalid status' => [['name' => 'A', 'email' => 'a@b.test', 'status' => 'paused'], 'status'],
    ]);

    it('refuses an email address another client already uses', function (): void {
        Client::factory()->create(['email' => 'hello@northwind.test']);

        actingAs(administrator())
            ->post(route('clients.store'), [
                'name' => 'Another Northwind',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
            ])
            ->assertSessionHasErrors('email');
    });

    it('refuses a user without the create permission', function (): void {
        actingAs(administratorWithout([PermissionName::ClientsCreate]))
            ->post(route('clients.store'), [
                'name' => 'Northwind Studio',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
            ])
            ->assertForbidden();

        expect(Client::query()->count())->toBe(0);
    });
});

describe('applying default notifications while creating a client', function (): void {
    it('creates the schedules the user ticked', function (): void {
        $monthly = DefaultClientNotification::factory()
            ->forTemplate(EmailTemplate::MonthlyReminder, NotificationFrequency::Monthly)
            ->create();

        $annual = DefaultClientNotification::factory()
            ->forTemplate(EmailTemplate::AnnualReminder, NotificationFrequency::Yearly)
            ->create();

        $firstSend = CarbonImmutable::now()->addDays(10)->setTime(9, 0);

        actingAs(administrator())
            ->post(route('clients.store'), [
                'name' => 'Northwind Studio',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
                'schedules' => [
                    ['default_id' => $monthly->id, 'apply' => '1', 'starts_at' => $firstSend->format('Y-m-d\TH:i'), 'is_enabled' => '1'],
                    ['default_id' => $annual->id, 'apply' => '0', 'starts_at' => '', 'is_enabled' => '1'],
                ],
            ])
            ->assertRedirect();

        $client = Client::query()->firstOrFail();

        expect($client->notificationSchedules)->toHaveCount(1)
            ->and($client->notificationSchedules->first()->template)->toBe(EmailTemplate::MonthlyReminder)
            ->and($client->notificationSchedules->first()->frequency)->toBe(NotificationFrequency::Monthly)
            ->and($client->notificationSchedules->first()->next_send_at->format('Y-m-d H:i'))
            ->toBe($firstSend->format('Y-m-d H:i'));
    });

    it('requires a first send date for a schedule that is being applied', function (): void {
        $default = DefaultClientNotification::factory()->create();

        actingAs(administrator())
            ->post(route('clients.store'), [
                'name' => 'Northwind Studio',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
                'schedules' => [
                    ['default_id' => $default->id, 'apply' => '1', 'starts_at' => '', 'is_enabled' => '1'],
                ],
            ])
            ->assertSessionHasErrors('schedules.0.starts_at');

        expect(Client::query()->count())->toBe(0);
    });

    it('refuses a first send date in the past', function (): void {
        $default = DefaultClientNotification::factory()->create();

        actingAs(administrator())
            ->post(route('clients.store'), [
                'name' => 'Northwind Studio',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
                'schedules' => [
                    [
                        'default_id' => $default->id,
                        'apply' => '1',
                        'starts_at' => CarbonImmutable::now()->subDay()->format('Y-m-d\TH:i'),
                        'is_enabled' => '1',
                    ],
                ],
            ])
            ->assertSessionHasErrors('schedules.0.starts_at');
    });

    it('takes the template and frequency from the stored default, not from the form', function (): void {
        $default = DefaultClientNotification::factory()
            ->forTemplate(EmailTemplate::AnnualReminder, NotificationFrequency::Yearly)
            ->create();

        actingAs(administrator())
            ->post(route('clients.store'), [
                'name' => 'Northwind Studio',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
                'schedules' => [
                    [
                        'default_id' => $default->id,
                        'apply' => '1',
                        'starts_at' => CarbonImmutable::now()->addDays(3)->format('Y-m-d\TH:i'),
                        'is_enabled' => '1',
                        // A tampered payload trying to pick a different template.
                        'template' => EmailTemplate::GeneralReminder->value,
                        'frequency' => NotificationFrequency::Monthly->value,
                    ],
                ],
            ])
            ->assertRedirect();

        $schedule = NotificationSchedule::query()->firstOrFail();

        expect($schedule->template)->toBe(EmailTemplate::AnnualReminder)
            ->and($schedule->frequency)->toBe(NotificationFrequency::Yearly);
    });

    it('does not change schedules that were already created when the defaults change later', function (): void {
        $default = DefaultClientNotification::factory()
            ->forTemplate(EmailTemplate::MonthlyReminder, NotificationFrequency::Monthly)
            ->create();

        actingAs(administrator())
            ->post(route('clients.store'), [
                'name' => 'Northwind Studio',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
                'schedules' => [
                    [
                        'default_id' => $default->id,
                        'apply' => '1',
                        'starts_at' => CarbonImmutable::now()->addDays(3)->format('Y-m-d\TH:i'),
                        'is_enabled' => '1',
                    ],
                ],
            ]);

        $schedule = NotificationSchedule::query()->firstOrFail();

        actingAs(administrator())
            ->put(route('admin.default-client-notifications.update'), [
                'entries' => [
                    ['template' => EmailTemplate::AnnualReminder->value, 'frequency' => NotificationFrequency::Yearly->value, 'is_enabled_by_default' => '1'],
                ],
            ])
            ->assertRedirect();

        expect($schedule->fresh()->template)->toBe(EmailTemplate::MonthlyReminder)
            ->and($schedule->fresh()->frequency)->toBe(NotificationFrequency::Monthly);
    });
});

describe('updating a client', function (): void {
    it('updates the details and records the change', function (): void {
        $client = Client::factory()->create(['name' => 'Old Name']);

        actingAs(administrator())
            ->put(route('clients.update', $client), [
                'name' => 'New Name',
                'email' => $client->email->value,
                'status' => ClientStatus::Inactive->value,
                'notes' => null,
            ])
            ->assertRedirect(route('clients.show', $client));

        expect($client->fresh()->name)->toBe('New Name')
            ->and($client->fresh()->status)->toBe(ClientStatus::Inactive);

        $audit = Audit::query()->where('action', AuditAction::ClientUpdated->value)->firstOrFail();

        expect($audit->old_values['name'])->toBe('Old Name')
            ->and($audit->new_values['name'])->toBe('New Name');
    });

    it('lets a client keep its own email address', function (): void {
        $client = Client::factory()->create(['email' => 'hello@northwind.test']);

        actingAs(administrator())
            ->put(route('clients.update', $client), [
                'name' => 'Northwind Studio',
                'email' => 'hello@northwind.test',
                'status' => ClientStatus::Active->value,
            ])
            ->assertSessionHasNoErrors();
    });

    it('refuses a user without the update permission', function (): void {
        $client = Client::factory()->create(['name' => 'Untouched']);

        actingAs(administratorWithout([PermissionName::ClientsUpdate]))
            ->put(route('clients.update', $client), [
                'name' => 'Changed',
                'email' => $client->email->value,
                'status' => ClientStatus::Active->value,
            ])
            ->assertForbidden();

        expect($client->fresh()->name)->toBe('Untouched');
    });
});

describe('archiving and restoring a client', function (): void {
    it('archives the client, switches off its schedules and keeps its history', function (): void {
        $client = Client::factory()->create();
        $schedule = NotificationSchedule::factory()->for($client)->create();
        $delivery = NotificationDelivery::factory()->forSchedule($schedule)->create();

        actingAs(administrator())
            ->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        expect($client->fresh()->trashed())->toBeTrue()
            ->and($schedule->fresh()->is_enabled)->toBeFalse()
            ->and($schedule->fresh()->next_send_at)->toBeNull()
            ->and($delivery->fresh())->not->toBeNull();

        assertDatabaseHas('audits', ['action' => AuditAction::ClientArchived->value]);
    });

    it('restores an archived client without switching its schedules back on', function (): void {
        $client = Client::factory()->archived()->create();
        $schedule = NotificationSchedule::factory()->for($client)->disabled()->create();

        actingAs(administrator())
            ->post(route('clients.restore', $client))
            ->assertRedirect(route('clients.show', $client));

        expect($client->fresh()->trashed())->toBeFalse()
            ->and($schedule->fresh()->is_enabled)->toBeFalse();

        assertDatabaseHas('audits', ['action' => AuditAction::ClientRestored->value]);
    });

    it('refuses archiving without the delete permission', function (): void {
        $client = Client::factory()->create();

        actingAs(administratorWithout([PermissionName::ClientsDelete]))
            ->delete(route('clients.destroy', $client))
            ->assertForbidden();

        expect($client->fresh()->trashed())->toBeFalse();
    });
});

describe('viewing a client', function (): void {
    it('shows the client with its schedules', function (): void {
        $client = Client::factory()->withNotes()->create(['name' => 'Northwind Studio']);
        NotificationSchedule::factory()->for($client)->create();

        actingAs(administrator())
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Northwind Studio')
            ->assertSee($client->notes);
    });

    /*
     * Eloquent only marks models as protected from lazy loading when a query returned
     * more than one row, so a client with a single schedule cannot show a missing eager
     * load. Two schedules are what makes the send action's policy read its client for
     * real, which is how this page once broke.
     */
    it('offers the send action on every schedule of a client', function (): void {
        $client = Client::factory()->create();
        $schedules = NotificationSchedule::factory()->for($client)->count(2)->create();

        $response = actingAs(administrator())
            ->get(route('clients.show', $client))
            ->assertOk();

        foreach ($schedules as $schedule) {
            $response->assertSee(route('cadence.schedules.send', $schedule), escape: false);
        }
    });

    // Archived clients have their own suite: tests/Feature/Clients/ArchivedClientTest.php
});
