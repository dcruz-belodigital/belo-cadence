<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\Enums\PermissionName;
use App\Mail\NotificationMail;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

/**
 * A schedule that answers to nobody in the client list: it is named by hand and sends to
 * the addresses it was given, which is how anything that is not about one client — an
 * internal digest, a partner notice — gets scheduled.
 */
function listScheduleForm(array $overrides = []): array
{
    return array_merge([
        'target' => NotificationTarget::Recipients->value,
        'name' => 'Weekly ops digest',
        'recipients' => "ops@belo.test\nfinance@belo.test",
        'template' => EmailTemplate::StatusUpdate->value,
        'frequency' => NotificationFrequency::Monthly->value,
        'starts_at' => CarbonImmutable::now()->addDays(7)->setTime(9, 0)->format('Y-m-d\TH:i'),
        'is_enabled' => '1',
    ], $overrides);
}

beforeEach(function (): void {
    Mail::fake();
});

describe('creating one', function (): void {
    it('creates a schedule that belongs to no client', function (): void {
        actingAs(administrator())
            ->post(route('cadence.schedules.store'), listScheduleForm())
            ->assertRedirect();

        $schedule = NotificationSchedule::query()->firstOrFail();

        expect($schedule->client_id)->toBeNull()
            ->and($schedule->target)->toBe(NotificationTarget::Recipients)
            ->and($schedule->name)->toBe('Weekly ops digest')
            ->and($schedule->displayName())->toBe('Weekly ops digest')
            ->and(array_map('strval', $schedule->recipients))->toBe(['ops@belo.test', 'finance@belo.test']);

        assertDatabaseHas('audits', ['action' => 'notification_schedule.created']);
    });

    it('normalises and de-duplicates the addresses it is given', function (): void {
        actingAs(administrator())
            ->post(route('cadence.schedules.store'), listScheduleForm([
                'recipients' => "  OPS@Belo.test \n ops@belo.test\nfinance@belo.test,ops@belo.test",
            ]))
            ->assertRedirect();

        expect(array_map('strval', NotificationSchedule::query()->firstOrFail()->recipients))
            ->toBe(['ops@belo.test', 'finance@belo.test']);
    });

    it('needs a name and at least one address', function (array $overrides, string $field): void {
        actingAs(administrator())
            ->post(route('cadence.schedules.store'), listScheduleForm($overrides))
            ->assertSessionHasErrors($field);
    })->with([
        'no name' => [['name' => ''], 'name'],
        'no recipients' => [['recipients' => ''], 'recipients'],
        'no target' => [['target' => ''], 'target'],
    ]);

    it('refuses an address that is not an address', function (): void {
        actingAs(administrator())
            ->post(route('cadence.schedules.store'), listScheduleForm([
                'recipients' => "ops@belo.test\nnot-an-address",
            ]))
            ->assertSessionHasErrors('recipients.1');

        expect(NotificationSchedule::query()->count())->toBe(0);
    });
});

describe('templates belong to an audience', function (): void {
    it('refuses a client template on a recipient list', function (): void {
        actingAs(administrator())
            ->post(route('cadence.schedules.store'), listScheduleForm([
                'template' => EmailTemplate::MonthlyReminder->value,
            ]))
            ->assertSessionHasErrors('template');
    });

    it('refuses a recipient-list template on a client', function (): void {
        $client = Client::factory()->create();

        actingAs(administrator())
            ->post(route('clients.schedules.store', $client), [
                'template' => EmailTemplate::StatusUpdate->value,
                'frequency' => NotificationFrequency::Monthly->value,
                'starts_at' => CarbonImmutable::now()->addDay()->format('Y-m-d\TH:i'),
                'is_enabled' => '1',
            ])
            ->assertSessionHasErrors('template');
    });

    it('offers the blank template to both', function (): void {
        expect(EmailTemplate::Blank->supports(NotificationTarget::Client))->toBeTrue()
            ->and(EmailTemplate::Blank->supports(NotificationTarget::Recipients))->toBeTrue()
            ->and(EmailTemplate::MonthlyReminder->supports(NotificationTarget::Recipients))->toBeFalse()
            ->and(EmailTemplate::StatusUpdate->supports(NotificationTarget::Client))->toBeFalse();
    });
});

describe('the blank template', function (): void {
    it('needs a subject and a message of its own', function (): void {
        actingAs(administrator())
            ->post(route('cadence.schedules.store'), listScheduleForm([
                'template' => EmailTemplate::Blank->value,
                'subject' => '',
                'message' => '',
            ]))
            ->assertSessionHasErrors(['subject', 'message']);
    });

    it('sends the wording written on the schedule', function (): void {
        actingAs(administrator())->post(route('cadence.schedules.store'), listScheduleForm([
            'template' => EmailTemplate::Blank->value,
            'subject' => 'Q3 figures are ready',
            'message' => "Hi all,\n\nThe numbers are in the shared drive.",
            'recipients' => 'ops@belo.test',
            'starts_at' => CarbonImmutable::now()->addMinutes(5)->format('Y-m-d\TH:i'),
        ]));

        $schedule = NotificationSchedule::query()->firstOrFail();
        $schedule->update(['next_send_at' => CarbonImmutable::now()->subMinute()]);

        processDueNotifications();

        $delivery = NotificationDelivery::query()->firstOrFail();

        expect($delivery->subject)->toBe('Q3 figures are ready')
            ->and($delivery->body_html)->toContain('Hi all,')
            ->and($delivery->body_html)->toContain('The numbers are in the shared drive.');
    });

    it('escapes what was typed rather than letting it become markup', function (): void {
        $schedule = NotificationSchedule::factory()
            ->forRecipients('Ops', ['ops@belo.test'])
            ->blank('A subject', '<script>alert(1)</script>')
            ->due()
            ->create();

        processDueNotifications();

        $delivery = NotificationDelivery::query()->firstOrFail();

        expect($delivery->body_html)->not->toContain('<script>')
            ->and($delivery->body_html)->toContain('&lt;script&gt;');
    });
});

describe('sending one', function (): void {
    it('sends one email and one delivery per recipient', function (): void {
        NotificationSchedule::factory()
            ->forRecipients('Weekly ops digest', ['ops@belo.test', 'finance@belo.test', 'legal@belo.test'])
            ->due()
            ->create();

        $result = processDueNotifications();

        expect($result->sent)->toBe(3)
            ->and($result->failed)->toBe(0);

        Mail::assertSent(NotificationMail::class, 3);

        $deliveries = NotificationDelivery::query()->orderBy('recipient_email')->get();

        expect($deliveries)->toHaveCount(3)
            ->and($deliveries->pluck('recipient_email')->map('strval')->all())
            ->toBe(['finance@belo.test', 'legal@belo.test', 'ops@belo.test']);

        // Every one of them snapshots the same occurrence and the same name.
        expect($deliveries->pluck('scheduled_for')->unique())->toHaveCount(1)
            ->and($deliveries->pluck('target_name')->unique()->all())->toBe(['Weekly ops digest'])
            ->and($deliveries->pluck('client_id')->unique()->all())->toBe([null]);
    });

    it('advances the schedule once, not once per recipient', function (): void {
        $occurrence = CarbonImmutable::parse('2026-03-15 09:00', 'UTC');

        $schedule = NotificationSchedule::factory()
            ->forRecipients('Ops', ['a@belo.test', 'b@belo.test'])
            ->monthly()
            ->scheduledFor($occurrence)
            ->create();

        processDueNotifications(CarbonImmutable::parse('2026-03-15 09:05', 'UTC'));

        expect($schedule->fresh()->next_send_at->format('Y-m-d H:i'))->toBe('2026-04-15 09:00')
            ->and($schedule->fresh()->last_sent_at->format('Y-m-d H:i'))->toBe('2026-03-15 09:00');
    });

    it('does not send the same occurrence to the same recipient twice', function (): void {
        NotificationSchedule::factory()
            ->forRecipients('Ops', ['ops@belo.test', 'finance@belo.test'])
            ->due()
            ->create();

        processDueNotifications();
        processDueNotifications();

        Mail::assertSent(NotificationMail::class, 2);

        expect(NotificationDelivery::query()->count())->toBe(2);
    });

    it('sends regardless of any client status, because it has no client', function (): void {
        // An inactive client stops its own schedules; a list schedule answers to nobody.
        Client::factory()->create(['status' => ClientStatus::Inactive]);

        NotificationSchedule::factory()
            ->forRecipients('Ops', ['ops@belo.test'])
            ->due()
            ->create();

        expect(processDueNotifications()->sent)->toBe(1);
    });

    it('spends the occurrence and warns when a schedule has no recipients left', function (): void {
        $schedule = NotificationSchedule::factory()
            ->forRecipients('Ops', ['ops@belo.test'])
            ->monthly()
            ->due()
            ->create();

        $schedule->update(['recipients' => []]);

        $result = processDueNotifications();

        expect($result->skipped)->toBe(1)
            ->and($result->sent)->toBe(0)
            ->and(NotificationDelivery::query()->count())->toBe(0)
            // The occurrence moved on, so the scheduler will not retry it forever.
            ->and($schedule->fresh()->next_send_at->greaterThan($schedule->next_send_at))->toBeTrue();

        Mail::assertNothingSent();
    });
});

describe('when one address on the list is bad', function (): void {
    beforeEach(function (): void {
        /*
        | A mailer that rejects exactly one address and delivers the rest, which is what a
        | single wrong entry on a list looks like from the application's point of view.
        */
        Mail::swap(new class(new MailManager(app())) extends MailFake
        {
            public function send($view, array $data = [], $callback = null)
            {
                if ($view instanceof NotificationMail && $view->data->recipientEmail->value === 'typo@belo.tst') {
                    throw new RuntimeException('Mailbox unavailable: the address was rejected.');
                }

                return parent::send($view, $data, $callback);
            }
        });
    });

    it('fails only that address and still sends the rest', function (): void {
        NotificationSchedule::factory()
            ->forRecipients('Weekly ops digest', ['ops@belo.test', 'typo@belo.tst', 'finance@belo.test'])
            ->due()
            ->create();

        $result = processDueNotifications();

        expect($result->sent)->toBe(2)
            ->and($result->failed)->toBe(1);

        $deliveries = NotificationDelivery::query()->get()->keyBy(fn (NotificationDelivery $d): string => $d->recipient_email->value);

        expect($deliveries)->toHaveCount(3)
            ->and($deliveries['ops@belo.test']->status)->toBe(NotificationDeliveryStatus::Sent)
            ->and($deliveries['finance@belo.test']->status)->toBe(NotificationDeliveryStatus::Sent)
            ->and($deliveries['typo@belo.tst']->status)->toBe(NotificationDeliveryStatus::Failed)
            ->and($deliveries['typo@belo.tst']->failure_message)->toContain('rejected')
            ->and($deliveries['typo@belo.tst']->sent_at)->toBeNull();
    });

    it('still records the send on the schedule, because something did go out', function (): void {
        $occurrence = CarbonImmutable::parse('2026-03-15 09:00', 'UTC');

        $schedule = NotificationSchedule::factory()
            ->forRecipients('Ops', ['ops@belo.test', 'typo@belo.tst'])
            ->monthly()
            ->scheduledFor($occurrence)
            ->create();

        processDueNotifications(CarbonImmutable::parse('2026-03-15 09:05', 'UTC'));

        expect($schedule->fresh()->last_sent_at->format('Y-m-d H:i'))->toBe('2026-03-15 09:00');
    });

    it('reports the partial failure when a person sent it by hand', function (): void {
        $schedule = NotificationSchedule::factory()
            ->forRecipients('Ops', ['ops@belo.test', 'typo@belo.tst'])
            ->create();

        actingAs(administrator())
            ->post(route('cadence.schedules.send', $schedule))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect(NotificationDelivery::query()->failed()->count())->toBe(1)
            ->and(NotificationDelivery::query()->sent()->count())->toBe(1);
    });
});

describe('managing one', function (): void {
    it('lists it alongside the client schedules and filters by target', function (): void {
        Client::factory()->create(['name' => 'Northwind Studio']);
        NotificationSchedule::factory()->for(Client::factory()->create(['name' => 'Harbour Pine']))->create();
        NotificationSchedule::factory()->forRecipients('Weekly ops digest')->create();

        $administrator = administrator();

        actingAs($administrator)
            ->get(route('cadence.schedules.index'))
            ->assertOk()
            ->assertSee('Harbour Pine')
            ->assertSee('Weekly ops digest');

        actingAs($administrator)
            ->get(route('cadence.schedules.index', ['target' => NotificationTarget::Recipients->value]))
            ->assertOk()
            ->assertSee('Weekly ops digest')
            ->assertDontSee('Harbour Pine');

        actingAs($administrator)
            ->get(route('cadence.schedules.index', ['target' => NotificationTarget::Client->value]))
            ->assertOk()
            ->assertSee('Harbour Pine')
            ->assertDontSee('Weekly ops digest');
    });

    it('finds it by name in a search', function (): void {
        NotificationSchedule::factory()->forRecipients('Weekly ops digest')->create();
        NotificationSchedule::factory()->forRecipients('Quarterly board note')->create();

        actingAs(administrator())
            ->get(route('cadence.schedules.index', ['search' => 'ops']))
            ->assertOk()
            ->assertSee('Weekly ops digest')
            ->assertDontSee('Quarterly board note');
    });

    it('edits its name and its addresses', function (): void {
        $schedule = NotificationSchedule::factory()
            ->forRecipients('Weekly ops digest', ['ops@belo.test'])
            ->create();

        actingAs(administrator())
            ->put(route('cadence.schedules.update', $schedule), [
                'name' => 'Daily ops digest',
                'recipients' => "ops@belo.test\nfinance@belo.test",
                'template' => EmailTemplate::ActionRequired->value,
                'frequency' => $schedule->frequency->value,
                'starts_at' => $schedule->starts_at->format('Y-m-d\TH:i'),
                'is_enabled' => '1',
            ])
            ->assertRedirect(route('cadence.schedules.show', $schedule))
            ->assertSessionHasNoErrors();

        $schedule->refresh();

        expect($schedule->name)->toBe('Daily ops digest')
            ->and(array_map('strval', $schedule->recipients))->toBe(['ops@belo.test', 'finance@belo.test'])
            ->and($schedule->template)->toBe(EmailTemplate::ActionRequired);
    });

    it('never lets an edit change what a schedule targets', function (): void {
        $client = Client::factory()->create();
        $schedule = NotificationSchedule::factory()->for($client)->create();

        actingAs(administrator())->put(route('cadence.schedules.update', $schedule), [
            // A tampered payload trying to turn a client schedule into a list one.
            'target' => NotificationTarget::Recipients->value,
            'name' => 'Sneaky list',
            'recipients' => 'somewhere@else.test',
            'template' => $schedule->template->value,
            'frequency' => $schedule->frequency->value,
            'starts_at' => $schedule->starts_at->format('Y-m-d\TH:i'),
            'is_enabled' => '1',
        ]);

        $schedule->refresh();

        expect($schedule->client_id)->toBe($client->getKey())
            ->and($schedule->target)->toBe(NotificationTarget::Client)
            ->and($schedule->name)->toBeNull()
            ->and($schedule->recipients)->toBeNull();
    });

    it('deletes back to the schedules list rather than to a client page', function (): void {
        $schedule = NotificationSchedule::factory()->forRecipients()->create();

        actingAs(administrator())
            ->delete(route('cadence.schedules.destroy', $schedule))
            ->assertRedirect(route('cadence.schedules.index'));

        expect($schedule->fresh()->trashed())->toBeTrue();
    });
});

describe('authorisation', function (): void {
    it('refuses the general create form without the create permission', function (): void {
        actingAs(administratorWithout([PermissionName::NotificationsCreate]))
            ->get(route('cadence.schedules.create'))
            ->assertForbidden();
    });

    it('refuses creating one without the permission', function (): void {
        actingAs(administratorWithout([PermissionName::NotificationsCreate]))
            ->post(route('cadence.schedules.store'), listScheduleForm())
            ->assertForbidden();

        expect(NotificationSchedule::query()->count())->toBe(0);
    });
});

describe('sending a schedule by hand', function (): void {
    it('sends a list schedule now without touching its recurrence', function (): void {
        $schedule = NotificationSchedule::factory()
            ->forRecipients('Weekly ops digest', ['ops@belo.test', 'finance@belo.test'])
            ->monthly()
            ->create();

        $before = $schedule->only(['next_send_at', 'last_sent_at', 'is_enabled']);

        actingAs(administrator())
            ->post(route('cadence.schedules.send', $schedule))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(NotificationMail::class, 2);

        $deliveries = NotificationDelivery::query()->get();

        expect($deliveries)->toHaveCount(2)
            ->and($deliveries->every(fn (NotificationDelivery $d): bool => $d->is_manual))->toBeTrue()
            ->and($deliveries->pluck('notification_schedule_id')->unique()->all())->toBe([null])
            ->and($deliveries->pluck('target_name')->unique()->all())->toBe(['Weekly ops digest'])
            ->and($schedule->fresh()->only(['next_send_at', 'last_sent_at', 'is_enabled']))->toEqual($before);
    });

    it('sends a client schedule now', function (): void {
        $client = Client::factory()->create(['name' => 'Northwind Studio']);
        $schedule = NotificationSchedule::factory()->for($client)->create();

        actingAs(administrator())
            ->post(route('cadence.schedules.send', $schedule))
            ->assertRedirect()
            ->assertSessionHas('success');

        $delivery = NotificationDelivery::query()->firstOrFail();

        expect($delivery->is_manual)->toBeTrue()
            ->and($delivery->client_id)->toBe($client->getKey())
            ->and($delivery->recipient_email->value)->toBe($client->email->value)
            ->and($delivery->status)->toBe(NotificationDeliveryStatus::Sent);
    });

    it('refuses to send a client schedule whose client may not be emailed', function (): void {
        $client = Client::factory()->create(['status' => ClientStatus::Inactive]);
        $schedule = NotificationSchedule::factory()->for($client)->create();

        actingAs(administrator())
            ->post(route('cadence.schedules.send', $schedule))
            ->assertForbidden();

        Mail::assertNothingSent();
    });

    it('refuses to send without the send permission', function (): void {
        $schedule = NotificationSchedule::factory()->forRecipients()->create();

        actingAs(administratorWithout([PermissionName::NotificationsSend]))
            ->post(route('cadence.schedules.send', $schedule))
            ->assertForbidden();

        Mail::assertNothingSent();
    });
});

describe('sending to typed addresses by hand', function (): void {
    it('sends to a list nobody had to create first', function (): void {
        actingAs(administrator())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => NotificationTarget::Recipients->value,
                'name' => 'Incident update',
                'recipients' => "ops@belo.test\nlegal@belo.test",
                'template' => EmailTemplate::ActionRequired->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(NotificationMail::class, 2);

        $deliveries = NotificationDelivery::query()->get();

        expect($deliveries)->toHaveCount(2)
            ->and($deliveries->pluck('target_name')->unique()->all())->toBe(['Incident update'])
            ->and($deliveries->pluck('client_id')->unique()->all())->toBe([null])
            ->and($deliveries->every(fn (NotificationDelivery $d): bool => $d->is_manual))->toBeTrue();
    });

    it('needs a name and addresses when nothing is a client', function (): void {
        actingAs(administrator())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => NotificationTarget::Recipients->value,
                'template' => EmailTemplate::StatusUpdate->value,
            ])
            ->assertSessionHasErrors(['name', 'recipients']);

        Mail::assertNothingSent();
    });

    it('sends a typed blank message', function (): void {
        actingAs(administrator())
            ->post(route('cadence.deliveries.send.store'), [
                'target' => NotificationTarget::Recipients->value,
                'name' => 'Board note',
                'recipients' => 'board@belo.test',
                'template' => EmailTemplate::Blank->value,
                'subject' => 'Figures attached',
                'message' => 'Please read before Thursday.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $delivery = NotificationDelivery::query()->firstOrFail();

        expect($delivery->subject)->toBe('Figures attached')
            ->and($delivery->body_html)->toContain('Please read before Thursday.');
    });
});
