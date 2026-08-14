<?php

declare(strict_types=1);

use App\Actions\ClientNotifications\ProcessDueClientNotificationsAction;
use App\Actions\ClientNotifications\SendClientNotificationAction;
use App\Data\ClientNotifications\ProcessDueNotificationsResult;
use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationDeliveryStatus;
use App\Enums\ClientNotificationFrequency;
use App\Enums\ClientStatus;
use App\Enums\PermissionName;
use App\Mail\ClientNotificationMail;
use App\Models\ApplicationSettings;
use App\Models\Client;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;
use App\Notifications\ClientNotificationDeliveryFailedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Testing\Fakes\MailFake;

use function Pest\Laravel\artisan;

function processDueNotifications(?CarbonImmutable $at = null): ProcessDueNotificationsResult
{
    return app(ProcessDueClientNotificationsAction::class)($at);
}

function dueSchedule(array $scheduleAttributes = [], array $clientAttributes = []): ClientNotificationSchedule
{
    $client = Client::factory()->create($clientAttributes);

    return ClientNotificationSchedule::factory()
        ->for($client)
        ->due()
        ->create($scheduleAttributes);
}

beforeEach(function (): void {
    Mail::fake();
    Notification::fake();
});

describe('choosing what to send', function (): void {
    it('sends a notification whose occurrence has arrived', function (): void {
        $schedule = dueSchedule();

        $result = processDueNotifications();

        expect($result->sent)->toBe(1)
            ->and($result->failed)->toBe(0);

        Mail::assertSent(ClientNotificationMail::class, 1);

        expect(ClientNotificationDelivery::query()->count())->toBe(1);
    });

    it('leaves a notification that is not due yet alone', function (): void {
        ClientNotificationSchedule::factory()->create([
            'next_send_at' => CarbonImmutable::now()->addDay(),
        ]);

        expect(processDueNotifications()->total())->toBe(0);

        Mail::assertNothingSent();
    });

    it('ignores a disabled schedule', function (): void {
        ClientNotificationSchedule::factory()->due()->create(['is_enabled' => false]);

        expect(processDueNotifications()->total())->toBe(0);

        Mail::assertNothingSent();
    });

    it('ignores a schedule without a next occurrence', function (): void {
        ClientNotificationSchedule::factory()->create(['next_send_at' => null]);

        expect(processDueNotifications()->total())->toBe(0);
    });

    it('never emails an inactive client', function (): void {
        dueSchedule(clientAttributes: ['status' => ClientStatus::Inactive]);

        expect(processDueNotifications()->total())->toBe(0);

        Mail::assertNothingSent();
    });

    it('never emails an archived client', function (): void {
        $schedule = dueSchedule();
        $schedule->client->delete();

        expect(processDueNotifications()->total())->toBe(0);

        Mail::assertNothingSent();
    });
});

describe('the message that is sent', function (): void {
    it('uses the client address, the configured sender and the template subject', function (): void {
        ApplicationSettings::defaults()->fill([
            'application_name' => 'Belo Cadence',
            'client_email_sender_name' => 'Belo Cadence Team',
            'client_email_sender_email' => 'cadence@belo.test',
        ])->save();

        $schedule = dueSchedule(['template' => ClientEmailTemplate::AnnualReminder]);
        $client = $schedule->client;

        processDueNotifications();

        Mail::assertSent(ClientNotificationMail::class, function (ClientNotificationMail $mail) use ($client): bool {
            return $mail->hasTo($client->email->value)
                && $mail->hasFrom('cadence@belo.test', 'Belo Cadence Team')
                && $mail->data->subject === ClientEmailTemplate::AnnualReminder->subject([
                    'application' => 'Belo Cadence',
                    'client' => $client->name,
                ]);
        });
    });

    it('renders the blade view that belongs to the selected template', function (): void {
        $schedule = dueSchedule(['template' => ClientEmailTemplate::MonthlyReminder]);

        processDueNotifications();

        $delivery = ClientNotificationDelivery::query()->firstOrFail();

        $monthlyLine = __('mail.client_notifications.monthly_reminder.lines', ['application' => 'Belo Cadence'])[0];

        expect($delivery->body_html)->toContain($monthlyLine)
            ->and($delivery->body_html)->toContain($schedule->client->name);
    });
});

describe('the delivery record', function (): void {
    it('snapshots the recipient, the sender, the subject and the rendered message', function (): void {
        $schedule = dueSchedule();
        $client = $schedule->client;
        $settings = ApplicationSettings::current();

        processDueNotifications();

        $delivery = ClientNotificationDelivery::query()->firstOrFail();

        expect($delivery->client_id)->toBe($client->getKey())
            ->and($delivery->client_notification_schedule_id)->toBe($schedule->getKey())
            ->and($delivery->template)->toBe($schedule->template)
            ->and($delivery->recipient_email->value)->toBe($client->email->value)
            ->and($delivery->recipient_name)->toBe($client->name)
            ->and($delivery->sender_email->value)->toBe($settings->client_email_sender_email->value)
            ->and($delivery->sender_name)->toBe($settings->client_email_sender_name)
            ->and($delivery->subject)->not->toBeEmpty()
            ->and($delivery->body_html)->toContain('<html')
            ->and($delivery->scheduled_for->format('Y-m-d H:i'))->toBe($schedule->next_send_at->format('Y-m-d H:i'))
            ->and($delivery->status)->toBe(ClientNotificationDeliveryStatus::Sent)
            ->and($delivery->sent_at)->not->toBeNull()
            ->and($delivery->attempted_at)->not->toBeNull()
            ->and($delivery->failure_message)->toBeNull();
    });

    it('records the occurrence that was sent, not the moment of sending', function (): void {
        $occurrence = CarbonImmutable::now()->subHours(3)->startOfHour();

        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->scheduledFor($occurrence)
            ->create();

        processDueNotifications();

        expect(ClientNotificationDelivery::query()->firstOrFail()->scheduled_for->format('Y-m-d H:i'))
            ->toBe($occurrence->format('Y-m-d H:i'));
    });
});

describe('advancing the schedule', function (): void {
    it('moves a monthly schedule to the next occurrence and records the last send', function (): void {
        $occurrence = CarbonImmutable::parse('2026-03-15 09:00', 'UTC');

        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->monthly()
            ->scheduledFor($occurrence)
            ->create();

        processDueNotifications(CarbonImmutable::parse('2026-03-15 09:05', 'UTC'));

        $schedule->refresh();

        expect($schedule->next_send_at->format('Y-m-d H:i'))->toBe('2026-04-15 09:00')
            ->and($schedule->last_sent_at->format('Y-m-d H:i'))->toBe('2026-03-15 09:00')
            ->and($schedule->is_enabled)->toBeTrue();
    });

    it('moves a yearly schedule to the next year', function (): void {
        $occurrence = CarbonImmutable::parse('2026-03-15 09:00', 'UTC');

        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->yearly()
            ->scheduledFor($occurrence)
            ->create();

        processDueNotifications(CarbonImmutable::parse('2026-03-15 09:05', 'UTC'));

        expect($schedule->fresh()->next_send_at->format('Y-m-d H:i'))->toBe('2027-03-15 09:00');
    });

    it('closes a one-time schedule once its single occurrence has been handled', function (): void {
        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->oneTime()
            ->due()
            ->create();

        processDueNotifications();

        $schedule->refresh();

        expect($schedule->next_send_at)->toBeNull()
            ->and($schedule->is_enabled)->toBeFalse()
            ->and($schedule->isCompleted())->toBeTrue();

        // A second run has nothing left to do.
        expect(processDueNotifications()->total())->toBe(0);

        Mail::assertSent(ClientNotificationMail::class, 1);
    });

    it('sends one occurrence per run when a schedule has fallen behind', function (): void {
        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->monthly()
            ->scheduledFor(CarbonImmutable::parse('2026-01-15 09:00', 'UTC'))
            ->create();

        $now = CarbonImmutable::parse('2026-03-20 09:00', 'UTC');

        expect(processDueNotifications($now)->sent)->toBe(1)
            ->and($schedule->fresh()->next_send_at->format('Y-m-d'))->toBe('2026-02-15');

        expect(processDueNotifications($now)->sent)->toBe(1)
            ->and($schedule->fresh()->next_send_at->format('Y-m-d'))->toBe('2026-03-15');

        expect(processDueNotifications($now)->sent)->toBe(1)
            ->and($schedule->fresh()->next_send_at->format('Y-m-d'))->toBe('2026-04-15');

        expect(processDueNotifications($now)->total())->toBe(0);

        Mail::assertSent(ClientNotificationMail::class, 3);
    });
});

describe('duplicate protection', function (): void {
    it('does not send the same occurrence twice when processing runs again', function (): void {
        $schedule = dueSchedule();

        processDueNotifications();
        processDueNotifications();

        Mail::assertSent(ClientNotificationMail::class, 1);

        expect(ClientNotificationDelivery::query()->count())->toBe(1);
    });

    it('refuses a second attempt at an occurrence that was already recorded', function (): void {
        $schedule = dueSchedule();
        $occurrence = $schedule->next_send_at;

        $first = app(SendClientNotificationAction::class)($schedule, $occurrence);

        // A second process still holding the old state tries the same occurrence.
        $stale = ClientNotificationSchedule::query()->findOrFail($schedule->getKey());
        $stale->forceFill(['next_send_at' => $occurrence, 'is_enabled' => true]);

        $second = app(SendClientNotificationAction::class)($stale, $occurrence);

        expect($first)->not->toBeNull()
            ->and($second)->toBeNull();

        Mail::assertSent(ClientNotificationMail::class, 1);

        expect(ClientNotificationDelivery::query()->count())->toBe(1);
    });

    it('keeps the occurrence unique in the database', function (): void {
        $schedule = dueSchedule();
        $delivery = ClientNotificationDelivery::factory()->forSchedule($schedule)->create();

        expect(fn (): ClientNotificationDelivery => ClientNotificationDelivery::factory()
            ->forSchedule($schedule)
            ->create(['scheduled_for' => $delivery->scheduled_for]))
            ->toThrow(UniqueConstraintViolationException::class);
    });
});

describe('when sending fails', function (): void {
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

    it('keeps the attempt in history and marks it failed', function (): void {
        $schedule = dueSchedule();

        $result = processDueNotifications();

        expect($result->failed)->toBe(1)
            ->and($result->sent)->toBe(0);

        $delivery = ClientNotificationDelivery::query()->firstOrFail();

        expect($delivery->status)->toBe(ClientNotificationDeliveryStatus::Failed)
            ->and($delivery->sent_at)->toBeNull()
            ->and($delivery->failure_message)->toContain('timed out')
            ->and($delivery->body_html)->not->toBeEmpty()
            ->and($delivery->recipient_email->value)->toBe($schedule->client->email->value);
    });

    it('tells the people who watch delivery history', function (): void {
        $watcher = userWithPermissions([PermissionName::NotificationDeliveriesViewAny]);
        $other = userWithPermissions([PermissionName::ClientsViewAny]);
        $inactiveWatcher = userWithPermissions([PermissionName::NotificationDeliveriesViewAny], ['is_active' => false]);

        dueSchedule();

        processDueNotifications();

        Notification::assertSentTo($watcher, ClientNotificationDeliveryFailedNotification::class);
        Notification::assertNotSentTo($other, ClientNotificationDeliveryFailedNotification::class);
        Notification::assertNotSentTo($inactiveWatcher, ClientNotificationDeliveryFailedNotification::class);
    });

    it('still advances the schedule so one failure does not block the series', function (): void {
        $occurrence = CarbonImmutable::parse('2026-03-15 09:00', 'UTC');

        $schedule = ClientNotificationSchedule::factory()
            ->for(Client::factory())
            ->monthly()
            ->scheduledFor($occurrence)
            ->create();

        processDueNotifications(CarbonImmutable::parse('2026-03-15 09:05', 'UTC'));

        $schedule->refresh();

        expect($schedule->next_send_at->format('Y-m-d'))->toBe('2026-04-15')
            ->and($schedule->last_sent_at)->toBeNull();
    });
});

describe('the artisan command', function (): void {
    it('processes due notifications', function (): void {
        dueSchedule();

        artisan('cadence:process-notifications')->assertSuccessful();

        Mail::assertSent(ClientNotificationMail::class, 1);
    });

    it('reports when there is nothing to do', function (): void {
        artisan('cadence:process-notifications')->assertSuccessful();

        Mail::assertNothingSent();
    });
});

describe('the frequency catalogue', function (): void {
    it('has a blade view and a subject for every template', function (ClientEmailTemplate $template): void {
        expect(view()->exists($template->view()))->toBeTrue()
            ->and($template->subject(['application' => 'Belo Cadence', 'client' => 'A Client']))
            ->not->toContain('mail.client_notifications');
    })->with(ClientEmailTemplate::cases());

    it('has a label for every frequency', function (ClientNotificationFrequency $frequency): void {
        expect($frequency->label())->not->toContain('enums.');
    })->with(ClientNotificationFrequency::cases());
});
