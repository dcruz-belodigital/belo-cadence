<?php

declare(strict_types=1);

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTimeRange;
use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;

it('lists scheduled notifications nearest first', function (): void {
    $later = NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Later Client']))
        ->create(['next_send_at' => CarbonImmutable::now()->addDays(20)]);

    $sooner = NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Sooner Client']))
        ->create(['next_send_at' => CarbonImmutable::now()->addDay()]);

    $response = actingAs(administrator())->get(route('cadence.upcoming'))->assertOk();

    $body = $response->getContent();

    expect(strpos($body, 'Sooner Client'))->toBeLessThan(strpos($body, 'Later Client'));
});

it('leaves out schedules that have no upcoming occurrence', function (): void {
    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Disabled Client']))
        ->disabled()
        ->create();

    actingAs(administrator())
        ->get(route('cadence.upcoming'))
        ->assertOk()
        ->assertDontSee('Disabled Client')
        ->assertSee(__('cadence.upcoming.empty'));
});

it('narrows the list to a time range', function (): void {
    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Today Client']))
        ->create(['next_send_at' => CarbonImmutable::now()->addHours(2)]);

    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Next Month Client']))
        ->create(['next_send_at' => CarbonImmutable::now()->addDays(20)]);

    actingAs(administrator())
        ->get(route('cadence.upcoming', ['range' => NotificationTimeRange::Today->value]))
        ->assertOk()
        ->assertSee('Today Client')
        ->assertDontSee('Next Month Client');

    actingAs(administrator())
        ->get(route('cadence.upcoming', ['range' => NotificationTimeRange::Next30Days->value]))
        ->assertOk()
        ->assertSee('Today Client')
        ->assertSee('Next Month Client');
});

it('reads today in the timezone of the person looking', function (): void {
    // 23:30 in Tokyo is still the previous day in UTC.
    $tokyoLateEvening = CarbonImmutable::now('Asia/Tokyo')->setTime(23, 30);

    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Tokyo Evening Client']))
        ->create(['next_send_at' => $tokyoLateEvening->setTimezone('UTC')]);

    actingAs(administrator(['timezone' => 'Asia/Tokyo']))
        ->get(route('cadence.upcoming', ['range' => NotificationTimeRange::Today->value]))
        ->assertOk()
        ->assertSee('Tokyo Evening Client');
});

it('filters by template', function (): void {
    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Annual Client']))
        ->create(['template' => EmailTemplate::AnnualReminder]);

    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Monthly Client']))
        ->create(['template' => EmailTemplate::MonthlyReminder]);

    actingAs(administrator())
        ->get(route('cadence.upcoming', ['template' => EmailTemplate::AnnualReminder->value]))
        ->assertOk()
        ->assertSee('Annual Client')
        ->assertDontSee('Monthly Client');
});

it('filters by frequency', function (): void {
    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Yearly Client']))
        ->yearly()
        ->create();

    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Monthly Client']))
        ->monthly()
        ->create();

    actingAs(administrator())
        ->get(route('cadence.upcoming', ['frequency' => NotificationFrequency::Yearly->value]))
        ->assertOk()
        ->assertSee('Yearly Client')
        ->assertDontSee('Monthly Client');
});

it('searches by client name and address', function (): void {
    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Northwind Studio', 'email' => 'hello@northwind.test']))
        ->create();

    NotificationSchedule::factory()
        ->for(Client::factory()->create(['name' => 'Harbour & Pine', 'email' => 'accounts@harbour.test']))
        ->create();

    actingAs(administrator())
        ->get(route('cadence.upcoming', ['search' => 'northwind']))
        ->assertOk()
        ->assertSee('Northwind Studio')
        ->assertDontSee('Harbour');
});

it('refuses a user without the schedule permission', function (): void {
    actingAs(administratorWithout([PermissionName::NotificationsViewAny]))
        ->get(route('cadence.upcoming'))
        ->assertForbidden();
});
