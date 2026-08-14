<?php

declare(strict_types=1);

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationDeliveryStatus;
use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

describe('the history table', function (): void {
    it('lists deliveries with their client and status', function (): void {
        $delivery = ClientNotificationDelivery::factory()
            ->for(Client::factory()->create(['name' => 'Northwind Studio']))
            ->create(['subject' => 'Your monthly update']);

        actingAs(administrator())
            ->get(route('cadence.deliveries.index'))
            ->assertOk()
            ->assertSee('Northwind Studio')
            ->assertSee('Your monthly update')
            ->assertSee($delivery->status->label());
    });

    it('searches by subject and recipient', function (): void {
        ClientNotificationDelivery::factory()->create(['subject' => 'Annual review reminder']);
        ClientNotificationDelivery::factory()->create(['subject' => 'Something else entirely']);

        actingAs(administrator())
            ->get(route('cadence.deliveries.index', ['search' => 'Annual review']))
            ->assertOk()
            ->assertSee('Annual review reminder')
            ->assertDontSee('Something else entirely');
    });

    it('filters by status', function (): void {
        ClientNotificationDelivery::factory()->failed()->create(['subject' => 'Failed message']);
        ClientNotificationDelivery::factory()->sent()->create(['subject' => 'Delivered message']);

        actingAs(administrator())
            ->get(route('cadence.deliveries.index', ['status' => ClientNotificationDeliveryStatus::Failed->value]))
            ->assertOk()
            ->assertSee('Failed message')
            ->assertDontSee('Delivered message');
    });

    it('filters by client and template', function (): void {
        $client = Client::factory()->create(['name' => 'Northwind Studio']);

        ClientNotificationDelivery::factory()
            ->for($client)
            ->create(['subject' => 'Northwind message', 'template' => ClientEmailTemplate::AnnualReminder]);

        ClientNotificationDelivery::factory()->create(['subject' => 'Other message']);

        $administrator = administrator();

        actingAs($administrator)
            ->get(route('cadence.deliveries.index', ['client' => $client->getKey()]))
            ->assertOk()
            ->assertSee('Northwind message')
            ->assertDontSee('Other message');

        actingAs($administrator)
            ->get(route('cadence.deliveries.index', ['template' => ClientEmailTemplate::AnnualReminder->value]))
            ->assertOk()
            ->assertSee('Northwind message');
    });

    it('filters by date range in the reader timezone', function (): void {
        $delivery = ClientNotificationDelivery::factory()->create([
            'subject' => 'Within range',
            'scheduled_for' => CarbonImmutable::parse('2026-05-10 09:00', 'UTC'),
        ]);

        ClientNotificationDelivery::factory()->create([
            'subject' => 'Outside range',
            'scheduled_for' => CarbonImmutable::parse('2026-01-10 09:00', 'UTC'),
        ]);

        actingAs(administrator())
            ->get(route('cadence.deliveries.index', ['from' => '2026-05-01', 'to' => '2026-05-31']))
            ->assertOk()
            ->assertSee('Within range')
            ->assertDontSee('Outside range');
    });
});

describe('a single delivery', function (): void {
    it('shows the snapshot of what was sent', function (): void {
        $delivery = ClientNotificationDelivery::factory()->create([
            'subject' => 'Your annual reminder',
            'recipient_email' => 'client@example.test',
            'sender_email' => 'cadence@belo.test',
            'sender_name' => 'Belo Cadence',
        ]);

        actingAs(administrator())
            ->get(route('cadence.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Your annual reminder')
            ->assertSee('client@example.test')
            ->assertSee('cadence@belo.test')
            ->assertSee(__('deliveries.show.snapshot_notice'));
    });

    it('shows why a delivery failed', function (): void {
        $delivery = ClientNotificationDelivery::factory()->failed()->create();

        actingAs(administrator())
            ->get(route('cadence.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee(__('deliveries.show.failure'))
            ->assertSee('Connection to the mail server timed out.');
    });
});

describe('history is read-only', function (): void {
    it('has no route for changing or removing a delivery', function (): void {
        $delivery = ClientNotificationDelivery::factory()->create();

        actingAs(administrator());

        put('/cadence/deliveries/'.$delivery->getKey())->assertStatus(405);
        delete('/cadence/deliveries/'.$delivery->getKey())->assertStatus(405);
        post('/cadence/deliveries')->assertStatus(405);
        post('/cadence/deliveries/'.$delivery->getKey().'/resend')->assertNotFound();
    });

    it('never authorises changing a delivery, even for an administrator', function (): void {
        $delivery = ClientNotificationDelivery::factory()->create();
        $administrator = administrator();

        expect($administrator->can('update', $delivery))->toBeFalse()
            ->and($administrator->can('delete', $delivery))->toBeFalse()
            ->and($administrator->can('create', ClientNotificationDelivery::class))->toBeFalse();
    });

    it('offers no import for delivery history', function (): void {
        expect(Route::has('cadence.deliveries.import'))->toBeFalse();
    });
});

describe('historical integrity', function (): void {
    it('keeps the recipient and subject a delivery was sent with when the client changes', function (): void {
        $client = Client::factory()->create(['name' => 'Northwind Studio', 'email' => 'hello@northwind.test']);
        $schedule = ClientNotificationSchedule::factory()->for($client)->create();

        $delivery = ClientNotificationDelivery::factory()->forSchedule($schedule)->create([
            'recipient_email' => 'hello@northwind.test',
            'recipient_name' => 'Northwind Studio',
            'subject' => 'Your monthly update from Belo Cadence',
            'body_html' => '<p>Hello Northwind Studio,</p>',
        ]);

        actingAs(administrator())->put(route('clients.update', $client), [
            'name' => 'Northwind Group',
            'email' => 'new-address@northwind.test',
            'status' => $client->status->value,
        ]);

        $delivery->refresh();

        expect($delivery->recipient_email->value)->toBe('hello@northwind.test')
            ->and($delivery->recipient_name)->toBe('Northwind Studio')
            ->and($delivery->body_html)->toContain('Northwind Studio');
    });

    it('keeps the sender a delivery was sent with when the application settings change', function (): void {
        $delivery = ClientNotificationDelivery::factory()->create([
            'sender_email' => 'old-sender@belo.test',
            'sender_name' => 'Old Sender',
        ]);

        actingAs(administrator())->put(route('admin.settings.update'), [
            'application_name' => 'Belo Cadence',
            'default_locale' => 'en',
            'default_timezone' => 'UTC',
            'client_email_sender_name' => 'Brand New Sender',
            'client_email_sender_email' => 'new-sender@belo.test',
        ]);

        $delivery->refresh();

        expect($delivery->sender_email->value)->toBe('old-sender@belo.test')
            ->and($delivery->sender_name)->toBe('Old Sender');
    });

    it('keeps the stored message even when the template changes', function (): void {
        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->create(['template' => ClientEmailTemplate::MonthlyReminder]);

        $delivery = ClientNotificationDelivery::factory()->forSchedule($schedule)->create([
            'template' => ClientEmailTemplate::MonthlyReminder,
            'body_html' => '<p>The monthly wording as it was.</p>',
        ]);

        actingAs(administrator())->put(route('cadence.schedules.update', $schedule), [
            'template' => ClientEmailTemplate::AnnualReminder->value,
            'frequency' => $schedule->frequency->value,
            'starts_at' => $schedule->starts_at->format('Y-m-d\TH:i'),
            'is_enabled' => '1',
        ]);

        $delivery->refresh();

        expect($delivery->template)->toBe(ClientEmailTemplate::MonthlyReminder)
            ->and($delivery->body_html)->toBe('<p>The monthly wording as it was.</p>');
    });

    it('keeps deliveries when the client is archived', function (): void {
        $client = Client::factory()->create();
        $schedule = ClientNotificationSchedule::factory()->for($client)->create();
        $delivery = ClientNotificationDelivery::factory()->forSchedule($schedule)->create();

        actingAs(administrator())->delete(route('clients.destroy', $client));

        expect($delivery->fresh())->not->toBeNull();

        actingAs(administrator())
            ->get(route('cadence.deliveries.show', $delivery))
            ->assertOk();
    });
});

describe('authorisation', function (): void {
    it('refuses the history to a user without the permission', function (): void {
        actingAs(administratorWithout([PermissionName::NotificationDeliveriesViewAny]))
            ->get(route('cadence.deliveries.index'))
            ->assertForbidden();
    });

    it('hides deliveries on the client page from a user who may not see history', function (): void {
        $client = Client::factory()->create();
        $schedule = ClientNotificationSchedule::factory()->for($client)->create();
        ClientNotificationDelivery::factory()->forSchedule($schedule)->create(['subject' => 'Secret subject']);

        actingAs(administratorWithout([PermissionName::NotificationDeliveriesViewAny]))
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertDontSee('Secret subject');
    });
});
