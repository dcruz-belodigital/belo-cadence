<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function scheduleForm(array $overrides = []): array
{
    return array_merge([
        'template' => EmailTemplate::MonthlyReminder->value,
        'frequency' => NotificationFrequency::Monthly->value,
        'starts_at' => CarbonImmutable::now()->addDays(7)->setTime(9, 0)->format('Y-m-d\TH:i'),
        'is_enabled' => '1',
    ], $overrides);
}

describe('creating a schedule', function (): void {
    it('creates a schedule for a client and points it at the first occurrence', function (NotificationFrequency $frequency): void {
        $client = Client::factory()->create();
        $firstSend = CarbonImmutable::now()->addDays(7)->setTime(9, 0);

        actingAs(administrator())
            ->post(route('clients.schedules.store', $client), scheduleForm([
                'frequency' => $frequency->value,
                'starts_at' => $firstSend->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect(route('clients.show', $client));

        $schedule = NotificationSchedule::query()->firstOrFail();

        expect($schedule->client_id)->toBe($client->getKey())
            ->and($schedule->frequency)->toBe($frequency)
            ->and($schedule->is_enabled)->toBeTrue()
            ->and($schedule->starts_at->format('Y-m-d H:i'))->toBe($firstSend->format('Y-m-d H:i'))
            ->and($schedule->next_send_at->format('Y-m-d H:i'))->toBe($firstSend->format('Y-m-d H:i'))
            ->and($schedule->last_sent_at)->toBeNull();

        assertDatabaseHas('audits', [
            'action' => AuditAction::NotificationScheduleCreated->value,
            'auditable_type' => 'notification_schedule',
        ]);
    })->with([
        'one time' => NotificationFrequency::OneTime,
        'monthly' => NotificationFrequency::Monthly,
        'yearly' => NotificationFrequency::Yearly,
    ]);

    it('creates a disabled schedule without an upcoming occurrence', function (): void {
        $client = Client::factory()->create();

        actingAs(administrator())
            ->post(route('clients.schedules.store', $client), scheduleForm(['is_enabled' => '0']))
            ->assertRedirect();

        $schedule = NotificationSchedule::query()->firstOrFail();

        expect($schedule->is_enabled)->toBeFalse();
    });

    it('requires a first send date in the future', function (): void {
        $client = Client::factory()->create();

        actingAs(administrator())
            ->post(route('clients.schedules.store', $client), scheduleForm([
                'starts_at' => CarbonImmutable::now()->subDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('starts_at');
    });

    it('validates the template and the frequency', function (array $overrides, string $field): void {
        $client = Client::factory()->create();

        actingAs(administrator())
            ->post(route('clients.schedules.store', $client), scheduleForm($overrides))
            ->assertSessionHasErrors($field);
    })->with([
        'unknown template' => [['template' => 'weekly_digest'], 'template'],
        'unknown frequency' => [['frequency' => 'weekly'], 'frequency'],
        'missing date' => [['starts_at' => ''], 'starts_at'],
    ]);

    it('reads the first send date in the timezone of the person entering it', function (): void {
        $client = Client::factory()->create();
        $user = administrator(['timezone' => 'Asia/Tokyo']);

        actingAs($user)
            ->post(route('clients.schedules.store', $client), scheduleForm([
                'starts_at' => CarbonImmutable::now('Asia/Tokyo')->addDays(3)->setTime(9, 0)->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect();

        $schedule = NotificationSchedule::query()->firstOrFail();

        expect($schedule->starts_at->setTimezone('Asia/Tokyo')->format('H:i'))->toBe('09:00');
    });

    it('refuses a user without the create permission', function (): void {
        $client = Client::factory()->create();

        actingAs(administratorWithout([PermissionName::NotificationsCreate]))
            ->post(route('clients.schedules.store', $client), scheduleForm())
            ->assertForbidden();

        expect(NotificationSchedule::query()->count())->toBe(0);
    });
});

describe('updating a schedule', function (): void {
    it('recalculates the next occurrence when the anchor moves', function (): void {
        $schedule = NotificationSchedule::factory()
            ->for(Client::factory())
            ->monthly()
            ->create();

        $newAnchor = CarbonImmutable::now()->addDays(20)->setTime(11, 30);

        actingAs(administrator())
            ->put(route('cadence.schedules.update', $schedule), scheduleForm([
                'starts_at' => $newAnchor->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect(route('cadence.schedules.show', $schedule));

        expect($schedule->fresh()->next_send_at->format('Y-m-d H:i'))
            ->toBe($newAnchor->format('Y-m-d H:i'));
    });

    it('keeps the queued occurrence when only the template changes', function (): void {
        $schedule = NotificationSchedule::factory()
            ->for(Client::factory())
            ->monthly()
            ->create(['template' => EmailTemplate::MonthlyReminder]);

        $queued = $schedule->next_send_at;

        actingAs(administrator())
            ->put(route('cadence.schedules.update', $schedule), scheduleForm([
                'template' => EmailTemplate::GeneralReminder->value,
                'starts_at' => $schedule->starts_at->format('Y-m-d\TH:i'),
            ]))
            ->assertRedirect();

        $schedule->refresh();

        expect($schedule->template)->toBe(EmailTemplate::GeneralReminder)
            ->and($schedule->next_send_at->format('Y-m-d H:i'))->toBe($queued->format('Y-m-d H:i'));
    });

    it('accepts an anchor in the past for an existing schedule', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->monthly()->create();

        actingAs(administrator())
            ->put(route('cadence.schedules.update', $schedule), scheduleForm([
                'starts_at' => CarbonImmutable::now()->subMonths(3)->setTime(9, 0)->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasNoErrors();

        expect($schedule->fresh()->next_send_at)->not->toBeNull()
            ->and($schedule->fresh()->next_send_at->isFuture())->toBeTrue();
    });

    it('records the change', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();

        actingAs(administrator())
            ->put(route('cadence.schedules.update', $schedule), scheduleForm());

        assertDatabaseHas('audits', ['action' => AuditAction::NotificationScheduleUpdated->value]);
    });

    it('refuses a user without the update permission', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();

        actingAs(administratorWithout([PermissionName::NotificationsUpdate]))
            ->put(route('cadence.schedules.update', $schedule), scheduleForm())
            ->assertForbidden();
    });
});

describe('enabling and disabling a schedule', function (): void {
    it('disables a schedule and clears its upcoming occurrence', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();

        actingAs(administrator())
            ->post(route('cadence.schedules.disable', $schedule))
            ->assertRedirect();

        $schedule->refresh();

        expect($schedule->is_enabled)->toBeFalse()
            ->and($schedule->next_send_at)->toBeNull();

        assertDatabaseHas('audits', ['action' => AuditAction::NotificationScheduleDisabled->value]);
    });

    it('enables a schedule and resumes from the next future occurrence', function (): void {
        $schedule = NotificationSchedule::factory()
            ->for(Client::factory())
            ->monthly()
            ->disabled()
            ->create(['starts_at' => CarbonImmutable::now()->subMonths(4)->setTime(9, 0)]);

        actingAs(administrator())
            ->post(route('cadence.schedules.enable', $schedule))
            ->assertRedirect();

        $schedule->refresh();

        expect($schedule->is_enabled)->toBeTrue()
            ->and($schedule->next_send_at)->not->toBeNull()
            ->and($schedule->next_send_at->isFuture())->toBeTrue();

        assertDatabaseHas('audits', ['action' => AuditAction::NotificationScheduleEnabled->value]);
    });

    it('leaves a one-time schedule whose date has passed without an occurrence', function (): void {
        $schedule = NotificationSchedule::factory()
            ->for(Client::factory())
            ->oneTime()
            ->disabled()
            ->create(['starts_at' => CarbonImmutable::now()->subWeek()]);

        actingAs(administrator())->post(route('cadence.schedules.enable', $schedule));

        expect($schedule->fresh()->next_send_at)->toBeNull();
    });
});

describe('deleting a schedule', function (): void {
    it('removes the schedule but keeps its delivery history', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();
        $delivery = NotificationDelivery::factory()->forSchedule($schedule)->create();

        actingAs(administrator())
            ->delete(route('cadence.schedules.destroy', $schedule))
            ->assertRedirect(route('clients.show', $schedule->client));

        expect($schedule->fresh()->trashed())->toBeTrue()
            ->and($delivery->fresh()->status)->toBe($delivery->status);

        assertDatabaseHas('audits', ['action' => AuditAction::NotificationScheduleDeleted->value]);
    });

    it('still lets the delivery be opened afterwards', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();
        $delivery = NotificationDelivery::factory()->forSchedule($schedule)->create();

        $administrator = administrator();

        actingAs($administrator)->delete(route('cadence.schedules.destroy', $schedule));

        actingAs($administrator)
            ->get(route('cadence.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee(__('deliveries.show.schedule_deleted'));
    });

    it('refuses a user without the delete permission', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();

        actingAs(administratorWithout([PermissionName::NotificationsDelete]))
            ->delete(route('cadence.schedules.destroy', $schedule))
            ->assertForbidden();

        expect($schedule->fresh()->trashed())->toBeFalse();
    });
});

describe('viewing schedules', function (): void {
    it('lists every schedule with its client', function (): void {
        $client = Client::factory()->create(['name' => 'Northwind Studio']);
        NotificationSchedule::factory()->for($client)->create();

        actingAs(administrator())
            ->get(route('cadence.schedules.index'))
            ->assertOk()
            ->assertSee('Northwind Studio');
    });

    it('shows a schedule with its deliveries', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();
        $delivery = NotificationDelivery::factory()->forSchedule($schedule)->create();

        actingAs(administrator())
            ->get(route('cadence.schedules.show', $schedule))
            ->assertOk()
            ->assertSee($schedule->client->name)
            ->assertSee($delivery->subject);
    });

    it('keeps showing a schedule whose client was archived', function (): void {
        $schedule = NotificationSchedule::factory()->for(Client::factory())->create();
        $schedule->client->delete();

        actingAs(administrator())
            ->get(route('cadence.schedules.show', $schedule))
            ->assertOk();
    });
});
