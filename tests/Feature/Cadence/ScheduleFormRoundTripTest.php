<?php

declare(strict_types=1);

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Models\Client;
use App\Models\DefaultClientNotification;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;

/**
 * The first send date is typed in the reader's timezone and stored in UTC. These tests
 * pin down both directions of that conversion in the form itself, because getting it
 * wrong silently loses somebody's input.
 */
it('gives a rejected date back to the person in the form, in their own timezone', function (): void {
    $client = Client::factory()->create();
    $user = administrator(['timezone' => 'Asia/Tokyo']);

    // A past date in Tokyo, which the create form refuses.
    $typed = CarbonImmutable::now('Asia/Tokyo')->subDays(3)->setTime(9, 30)->format('Y-m-d\TH:i');

    actingAs($user)->post(route('clients.schedules.store', $client), [
        'template' => EmailTemplate::MonthlyReminder->value,
        'frequency' => NotificationFrequency::Monthly->value,
        'starts_at' => $typed,
        'is_enabled' => '1',
    ])->assertSessionHasErrors('starts_at');

    $form = actingAs($user)->get(route('clients.schedules.create', $client))->assertOk();

    expect($form->getContent())->toContain('value="'.$typed.'"');
});

it('shows an existing anchor in the reader timezone when editing', function (): void {
    // 23:30 UTC is 08:30 the next morning in Tokyo.
    $schedule = NotificationSchedule::factory()
        ->for(Client::factory())
        ->monthly()
        ->scheduledFor(CarbonImmutable::parse('2026-06-01 23:30', 'UTC'))
        ->create();

    $tokyo = administrator(['timezone' => 'Asia/Tokyo']);
    $utc = administrator(['timezone' => 'UTC']);

    expect(actingAs($tokyo)->get(route('cadence.schedules.edit', $schedule))->assertOk()->getContent())
        ->toContain('value="2026-06-02T08:30"');

    expect(actingAs($utc)->get(route('cadence.schedules.edit', $schedule))->assertOk()->getContent())
        ->toContain('value="2026-06-01T23:30"');
});

it('stores what the person meant, whatever timezone they are in', function (): void {
    $client = Client::factory()->create();
    $user = administrator(['timezone' => 'America/New_York']);

    $typed = CarbonImmutable::now('America/New_York')->addDays(4)->setTime(14, 15);

    actingAs($user)->post(route('clients.schedules.store', $client), [
        'template' => EmailTemplate::MonthlyReminder->value,
        'frequency' => NotificationFrequency::Monthly->value,
        'starts_at' => $typed->format('Y-m-d\TH:i'),
        'is_enabled' => '1',
    ])->assertSessionHasNoErrors();

    $schedule = NotificationSchedule::query()->firstOrFail();

    expect($schedule->starts_at->setTimezone('America/New_York')->format('Y-m-d H:i'))
        ->toBe($typed->format('Y-m-d H:i'))
        ->and($schedule->next_send_at->setTimezone('America/New_York')->format('H:i'))
        ->toBe('14:15');
});

it('keeps an unticked default unticked when the client form comes back with errors', function (): void {
    $default = DefaultClientNotification::factory()->create();

    actingAs(administrator())->put(route('admin.default-client-notifications.update'), [
        'entries' => [
            [
                'template' => $default->template->value,
                'frequency' => $default->frequency->value,
                'is_enabled_by_default' => '0',
            ],
            // An entry with no template is dropped, not reported.
            ['template' => '', 'frequency' => ''],
        ],
    ])->assertRedirect();

    expect($default->fresh()->is_enabled_by_default)->toBeFalse();

    $page = actingAs(administrator())
        ->get(route('admin.settings.edit'))
        ->assertOk();

    // The Alpine state must carry a real boolean: the string "0" would read as ticked.
    preg_match('/rows: JSON\\.parse\\(\'(.*?)\'\\)/s', $page->getContent(), $matches);

    $rows = json_decode(str_replace('\\u0022', '"', $matches[1] ?? '[]'), true, flags: JSON_THROW_ON_ERROR);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['is_enabled_by_default'])->toBeFalse();
});
