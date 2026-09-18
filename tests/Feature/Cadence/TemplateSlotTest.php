<?php

declare(strict_types=1);

use App\Actions\Notifications\SendScheduledNotificationAction;
use App\Enums\ClientAttributeType;
use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationFrequency;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeValue;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\ValueObjects\TemplateBinding;
use App\ValueObjects\TemplateBindings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

/**
 * A template leaves blanks; a schedule says what fills them. The value can come from one
 * of the client's attributes — including a field inside a repeater, at any depth — or be
 * written on the schedule itself.
 */
beforeEach(function (): void {
    Mail::fake();
    Notification::fake();
});

/**
 * Sends one schedule now and returns the body that went out.
 */
function sentBodyFor(NotificationSchedule $schedule): string
{
    $schedule->load('client');

    app(SendScheduledNotificationAction::class)($schedule, $schedule->next_send_at);

    return NotificationDelivery::query()->latest('id')->firstOrFail()->body_html ?? '';
}

function scheduleBound(Client $client, TemplateBindings $bindings): NotificationSchedule
{
    return NotificationSchedule::factory()->for($client)->create([
        'template' => EmailTemplate::AnnualReminder,
        'frequency' => NotificationFrequency::Yearly,
        'template_bindings' => $bindings,
        'next_send_at' => CarbonImmutable::now(),
    ]);
}

describe('filling a blank', function (): void {
    it('reads the answer the client gave for a plain attribute', function (): void {
        $renewal = ClientAttribute::factory()->ofType(ClientAttributeType::Date)->create(['name' => 'Renewal', 'position' => 1]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($renewal, '2026-03-01')->create();

        $schedule = scheduleBound($client, new TemplateBindings([
            'due_date' => TemplateBinding::attribute($renewal->getKey()),
        ]));

        expect(sentBodyFor($schedule))->toContain('It is due on 01 Mar 2026.');
    });

    it('uses a value written on the schedule when that is what was chosen', function (): void {
        $client = Client::factory()->create();

        $schedule = scheduleBound($client, new TemplateBindings([
            'due_date' => TemplateBinding::manual('the end of the quarter'),
        ]));

        expect(sentBodyFor($schedule))->toContain('It is due on the end of the quarter.');
    });

    it('reads one field out of a repeater, once per row', function (): void {
        $contacts = ClientAttribute::factory()->repeater()->create(['name' => 'Contacts', 'position' => 1]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($contacts, [
            ['name' => 'Ana Costa', 'extension' => 22],
            ['name' => 'Rui Silva', 'extension' => 23],
        ])->create();

        $schedule = scheduleBound($client, new TemplateBindings([
            'contacts' => TemplateBinding::attribute($contacts->getKey(), ['name']),
        ]));

        $body = sentBodyFor($schedule);

        expect($body)->toContain('Ana Costa')->toContain('Rui Silva')
            // Only the field that was asked for, not the rest of the row.
            ->and($body)->not->toContain('22');
    });

    it('reads a field out of a repeater inside a repeater', function (): void {
        $contacts = ClientAttribute::factory()->nestedRepeater()->create(['name' => 'Contacts', 'position' => 1]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($contacts, [
            ['name' => 'Ana', 'addresses' => [['city' => 'Porto'], ['city' => 'Lisboa']]],
            ['name' => 'Rui', 'addresses' => [['city' => 'Braga']]],
        ])->create();

        $schedule = scheduleBound($client, new TemplateBindings([
            'contacts' => TemplateBinding::attribute($contacts->getKey(), ['addresses', 'city']),
        ]));

        $body = sentBodyFor($schedule);

        expect($body)->toContain('Porto')->toContain('Lisboa')->toContain('Braga');
    });

    it('renders a whole repeater as its rows, field by field', function (): void {
        $contacts = ClientAttribute::factory()->repeater()->create(['name' => 'Contacts', 'position' => 1]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($contacts, [
            ['name' => 'Ana Costa', 'extension' => 22],
        ])->create();

        // The blank template takes anything, so it is where a whole repeater can land.
        $schedule = NotificationSchedule::factory()->for($client)->create([
            'template' => EmailTemplate::Blank,
            'subject' => 'Your contacts',
            'message' => 'We have these on file:

{{ contacts }}',
            'template_bindings' => new TemplateBindings([
                'contacts' => TemplateBinding::attribute($contacts->getKey()),
            ]),
            'next_send_at' => CarbonImmutable::now(),
        ]);

        expect(sentBodyFor($schedule))->toContain('Name: Ana Costa, Extension: 22');
    });

    it('leaves a blank nobody bound out of the message entirely', function (): void {
        $client = Client::factory()->create();

        $schedule = scheduleBound($client, new TemplateBindings);

        expect(sentBodyFor($schedule))->not->toContain('It is due on');
    });
});

describe('a promise that cannot be kept', function (): void {
    it('records the delivery as failed rather than sending a message with a hole in it', function (): void {
        $renewal = ClientAttribute::factory()->ofType(ClientAttributeType::Date)->create(['name' => 'Renewal', 'position' => 1]);

        // The attribute exists and is bound, but this client never answered it.
        $client = Client::factory()->create();

        $schedule = scheduleBound($client, new TemplateBindings([
            'due_date' => TemplateBinding::attribute($renewal->getKey()),
        ]));

        $schedule->load('client');
        app(SendScheduledNotificationAction::class)($schedule, $schedule->next_send_at);

        $delivery = NotificationDelivery::query()->firstOrFail();

        expect($delivery->status)->toBe(NotificationDeliveryStatus::Failed)
            ->and($delivery->failure_message)->toContain('Due date');

        Mail::assertNothingSent();

        // The occurrence was still claimed, so the scheduler does not retry forever.
        expect($schedule->fresh()->next_send_at)->not->toBeNull()
            ->and($schedule->fresh()->next_send_at->greaterThan(CarbonImmutable::now()))->toBeTrue();
    });

    it('fails the same way when the attribute has been retired', function (): void {
        $renewal = ClientAttribute::factory()->ofType(ClientAttributeType::Date)->create(['name' => 'Renewal', 'position' => 1]);

        $client = Client::factory()->create();
        ClientAttributeValue::factory()->for($client)->of($renewal, '2026-03-01')->create();

        $schedule = scheduleBound($client, new TemplateBindings([
            'due_date' => TemplateBinding::attribute($renewal->getKey()),
        ]));

        $renewal->update(['is_active' => false]);

        $schedule->load('client');
        app(SendScheduledNotificationAction::class)($schedule, $schedule->next_send_at);

        expect(NotificationDelivery::query()->firstOrFail()->status)->toBe(NotificationDeliveryStatus::Failed);
        Mail::assertNothingSent();
    });
});

describe('choosing the binding on the form', function (): void {
    it('saves what a schedule binds and offers it back when the schedule is reopened', function (): void {
        $renewal = ClientAttribute::factory()->ofType(ClientAttributeType::Date)->create(['name' => 'Renewal', 'position' => 1]);
        $client = Client::factory()->create();

        actingAs(administrator())->post(route('clients.schedules.store', $client), [
            'template' => EmailTemplate::AnnualReminder->value,
            'frequency' => NotificationFrequency::Yearly->value,
            'starts_at' => CarbonImmutable::now()->addWeek()->format('Y-m-d\TH:i'),
            'is_enabled' => '1',
            'template_bindings' => [
                'due_date' => ['source' => TemplateBinding::attribute($renewal->getKey())->token()],
                'contacts' => ['source' => TemplateBinding::MANUAL, 'value' => 'Ana on reception'],
            ],
        ])->assertSessionHasNoErrors();

        $schedule = NotificationSchedule::query()->firstOrFail();
        $bindings = $schedule->template_bindings;

        expect($bindings->for('due_date')->attributeId)->toBe($renewal->getKey())
            ->and($bindings->for('contacts')->value)->toBe('Ana on reception');

        actingAs(administrator())
            ->get(route('cadence.schedules.edit', $schedule))
            ->assertOk()
            ->assertSee($bindings->for('due_date')->token(), escape: false);
    });

    it('refuses to bind a client attribute on a schedule that has no client', function (): void {
        $renewal = ClientAttribute::factory()->ofType(ClientAttributeType::Date)->create(['name' => 'Renewal', 'position' => 1]);

        actingAs(administrator())->post(route('cadence.schedules.store'), [
            'target' => 'recipients',
            'name' => 'Weekly ops digest',
            'recipients' => "ops@example.test\n",
            'template' => EmailTemplate::Blank->value,
            'subject' => 'Ops',
            'message' => 'Due {{ due_date }}',
            'frequency' => NotificationFrequency::Yearly->value,
            'starts_at' => CarbonImmutable::now()->addWeek()->format('Y-m-d\TH:i'),
            'template_bindings' => [
                'due_date' => ['source' => TemplateBinding::attribute($renewal->getKey())->token()],
            ],
        ])->assertSessionHasErrors('template_bindings.due_date.source');
    });

    it('asks for a value when a value is what was chosen', function (): void {
        $client = Client::factory()->create();

        actingAs(administrator())->post(route('clients.schedules.store', $client), [
            'template' => EmailTemplate::AnnualReminder->value,
            'frequency' => NotificationFrequency::Yearly->value,
            'starts_at' => CarbonImmutable::now()->addWeek()->format('Y-m-d\TH:i'),
            'template_bindings' => [
                'due_date' => ['source' => TemplateBinding::MANUAL, 'value' => ''],
            ],
        ])->assertSessionHasErrors('template_bindings.due_date.value');
    });
});
