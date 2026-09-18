<?php

declare(strict_types=1);

use App\Actions\Notifications\SendManualNotificationAction;
use App\Data\Notifications\NotificationDispatch;
use App\Enums\ClientStatus;
use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\PermissionName;
use App\Mail\NotificationMail;
use App\Models\ApplicationSettings;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\User;
use App\Notifications\NotificationDeliveryFailedNotification;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Testing\Fakes\MailFake;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

/**
 * Any client email, to any client, at any moment somebody asks for it.
 *
 * A manual send produces an ordinary entry in delivery history — flagged as manual and
 * naming the person who asked — and leaves the schedules completely alone.
 */
function sender(array $attributes = []): User
{
    return userWithPermissions([
        PermissionName::NotificationsSend,
        PermissionName::NotificationDeliveriesViewAny,
        PermissionName::NotificationDeliveriesView,
    ], $attributes);
}

beforeEach(function (): void {
    Mail::fake();
    Notification::fake();
});

describe('sending by hand', function (): void {
    it('sends the chosen template to the chosen client straight away', function (): void {
        ApplicationSettings::defaults()->fill([
            'application_name' => 'Belo Cadence',
            'client_email_sender_name' => 'Belo Cadence Team',
            'client_email_sender_email' => 'cadence@belo.test',
        ])->save();

        $client = Client::factory()->create(['name' => 'Northwind Studio']);

        actingAs(sender())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => EmailTemplate::AnnualReminder->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail): bool => $mail->hasTo($client->email->value)
            && $mail->hasFrom('cadence@belo.test', 'Belo Cadence Team')
            && $mail->data->template === EmailTemplate::AnnualReminder);
    });

    it('records the delivery as manual, with the person who asked for it', function (): void {
        $actor = sender(['name' => 'Ada Sender']);
        $client = Client::factory()->create();

        actingAs($actor)->post(route('cadence.deliveries.send.store'), [
            'target' => 'client',
            'client' => $client->getKey(),
            'template' => EmailTemplate::GeneralReminder->value,
        ]);

        $delivery = NotificationDelivery::query()->firstOrFail();

        expect($delivery->is_manual)->toBeTrue()
            ->and($delivery->notification_schedule_id)->toBeNull()
            ->and($delivery->triggered_by_user_id)->toBe($actor->getKey())
            ->and($delivery->client_id)->toBe($client->getKey())
            ->and($delivery->template)->toBe(EmailTemplate::GeneralReminder)
            ->and($delivery->status)->toBe(NotificationDeliveryStatus::Sent)
            ->and($delivery->sent_at)->not->toBeNull();
    });

    it('snapshots the message exactly as a scheduled send does', function (): void {
        $client = Client::factory()->create(['name' => 'Northwind Studio']);

        actingAs(sender())->post(route('cadence.deliveries.send.store'), [
            'target' => 'client',
            'client' => $client->getKey(),
            'template' => EmailTemplate::MonthlyReminder->value,
        ]);

        $delivery = NotificationDelivery::query()->firstOrFail();
        $settings = ApplicationSettings::current();

        expect($delivery->recipient_email->value)->toBe($client->email->value)
            ->and($delivery->recipient_name)->toBe('Northwind Studio')
            ->and($delivery->sender_email->value)->toBe($settings->client_email_sender_email->value)
            ->and($delivery->sender_name)->toBe($settings->client_email_sender_name)
            ->and($delivery->subject)->not->toBeEmpty()
            ->and($delivery->body_html)->toContain('<html')
            ->and($delivery->body_html)->toContain('Northwind Studio');
    });

    it('needs no schedule and changes none', function (): void {
        $client = Client::factory()->create();
        $schedule = NotificationSchedule::factory()->for($client)->monthly()->create();
        $before = $schedule->only(['next_send_at', 'last_sent_at', 'is_enabled']);

        actingAs(sender())->post(route('cadence.deliveries.send.store'), [
            'target' => 'client',
            'client' => $client->getKey(),
            'template' => $schedule->template->value,
        ]);

        expect($schedule->fresh()->only(['next_send_at', 'last_sent_at', 'is_enabled']))->toEqual($before);
    });

    it('sends again when asked again, because asking twice means twice', function (): void {
        $client = Client::factory()->create();
        $actor = sender();

        foreach (range(1, 2) as $ignored) {
            actingAs($actor)->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => EmailTemplate::GeneralReminder->value,
            ]);
        }

        Mail::assertSent(NotificationMail::class, 2);

        expect(NotificationDelivery::query()->count())->toBe(2);
    });

    it('records nobody as the sender when nothing is signed in', function (): void {
        $client = Client::factory()->create();

        $delivery = app(SendManualNotificationAction::class)(new NotificationDispatch(
            template: EmailTemplate::GeneralReminder,
            clientId: $client->getKey(),
            targetName: $client->name,
            recipients: [$client->email],
            recipientName: $client->name,
        ))->firstOrFail();

        expect($delivery->is_manual)->toBeTrue()
            ->and($delivery->triggered_by_user_id)->toBeNull();
    });
});

describe('which clients may be emailed', function (): void {
    it('refuses an inactive client', function (): void {
        $client = Client::factory()->create(['status' => ClientStatus::Inactive]);

        actingAs(sender())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => EmailTemplate::GeneralReminder->value,
            ])
            ->assertSessionHasErrors('client');

        Mail::assertNothingSent();

        expect(NotificationDelivery::query()->count())->toBe(0);
    });

    it('refuses an archived client', function (): void {
        $client = Client::factory()->create();
        $client->delete();

        actingAs(sender())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => EmailTemplate::GeneralReminder->value,
            ])
            ->assertSessionHasErrors('client');

        Mail::assertNothingSent();
    });

    it('refuses a template that is not in the catalogue', function (): void {
        $client = Client::factory()->create();

        actingAs(sender())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => 'not_a_template',
            ])
            ->assertSessionHasErrors('template');

        Mail::assertNothingSent();
    });

    it('offers only clients that can be emailed on the form', function (): void {
        Client::factory()->create(['name' => 'Active Studio']);
        Client::factory()->create(['name' => 'Inactive Studio', 'status' => ClientStatus::Inactive]);
        Client::factory()->create(['name' => 'Archived Studio'])->delete();

        actingAs(sender())
            ->get(route('cadence.deliveries.send'))
            ->assertOk()
            ->assertSee('Active Studio')
            ->assertDontSee('Inactive Studio')
            ->assertDontSee('Archived Studio');
    });

    it('hides the button on a client who cannot be emailed', function (): void {
        $inactive = Client::factory()->create(['status' => ClientStatus::Inactive]);

        actingAs(administrator())
            ->get(route('clients.show', $inactive))
            ->assertOk()
            ->assertDontSee(route('cadence.deliveries.send', ['client' => $inactive->getKey()]));
    });
});

describe('when a manual send fails', function (): void {
    beforeEach(function (): void {
        // A mailer that renders normally but cannot deliver, which is what a mail server
        // outage looks like from the application's point of view.
        Mail::swap(new class(new MailManager(app())) extends MailFake
        {
            public function send($view, array $data = [], $callback = null)
            {
                throw new RuntimeException('Connection to the mail server timed out.');
            }
        });
    });

    it('keeps the attempt in history, marks it failed and says so', function (): void {
        $client = Client::factory()->create();

        actingAs(sender())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => EmailTemplate::GeneralReminder->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $delivery = NotificationDelivery::query()->firstOrFail();

        expect($delivery->status)->toBe(NotificationDeliveryStatus::Failed)
            ->and($delivery->is_manual)->toBeTrue()
            ->and($delivery->sent_at)->toBeNull()
            ->and($delivery->failure_message)->toContain('timed out')
            ->and($delivery->body_html)->not->toBeEmpty();
    });

    it('tells the people who watch delivery history, exactly as a scheduled failure does', function (): void {
        $watcher = userWithPermissions([PermissionName::NotificationDeliveriesViewAny]);
        $other = userWithPermissions([PermissionName::ClientsViewAny]);

        actingAs(sender())->post(route('cadence.deliveries.send.store'), [
            'target' => 'client',
            'client' => Client::factory()->create()->getKey(),
            'template' => EmailTemplate::GeneralReminder->value,
        ]);

        Notification::assertSentTo($watcher, NotificationDeliveryFailedNotification::class);
        Notification::assertNotSentTo($other, NotificationDeliveryFailedNotification::class);
    });
});

describe('history shows where a delivery came from', function (): void {
    it('marks a manual delivery and names who sent it', function (): void {
        $actor = sender(['name' => 'Ada Sender']);

        actingAs($actor)->post(route('cadence.deliveries.send.store'), [
            'target' => 'client',
            'client' => Client::factory()->create()->getKey(),
            'template' => EmailTemplate::GeneralReminder->value,
        ]);

        $delivery = NotificationDelivery::query()->firstOrFail();

        actingAs($actor)
            ->get(route('cadence.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee(__('deliveries.show.manual_notice'))
            ->assertSee(__('enums.notification_delivery_source.manual'))
            ->assertSee('Ada Sender')
            ->assertSee(__('deliveries.show.no_schedule'));
    });

    it('names the scheduler for a delivery no person asked for', function (): void {
        $delivery = NotificationDelivery::factory()->create();

        actingAs(administrator())
            ->get(route('cadence.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee(__('deliveries.show.sent_by_system'))
            ->assertSee(__('enums.notification_delivery_source.scheduled'));
    });
});

describe('authorisation', function (): void {
    it('refuses the form to a user without the send permission', function (): void {
        actingAs(administratorWithout([PermissionName::NotificationsSend]))
            ->get(route('cadence.deliveries.send'))
            ->assertForbidden();
    });

    it('refuses the send itself to a user without the permission', function (): void {
        $client = Client::factory()->create();

        actingAs(administratorWithout([PermissionName::NotificationsSend]))
            ->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => EmailTemplate::GeneralReminder->value,
            ])
            ->assertForbidden();

        Mail::assertNothingSent();
    });

    it('refuses a guest', function (): void {
        post(route('cadence.deliveries.send.store'), [
            'target' => 'client',
            'client' => Client::factory()->create()->getKey(),
            'template' => EmailTemplate::GeneralReminder->value,
        ])->assertRedirect(route('login'));

        Mail::assertNothingSent();
    });

    it('hides the entry points from a user who may not send', function (): void {
        actingAs(administratorWithout([PermissionName::NotificationsSend]))
            ->get(route('cadence.deliveries.index'))
            ->assertOk()
            ->assertDontSee(route('cadence.deliveries.send'));
    });

    it('returns somebody who may only send back to the form', function (): void {
        $client = Client::factory()->create();

        actingAs(userWithPermissions([PermissionName::NotificationsSend]))
            ->post(route('cadence.deliveries.send.store'), [
                'target' => 'client',
                'client' => $client->getKey(),
                'template' => EmailTemplate::GeneralReminder->value,
            ])
            ->assertRedirect(route('cadence.deliveries.send'))
            ->assertSessionHas('success');

        Mail::assertSent(NotificationMail::class, 1);
    });
});
