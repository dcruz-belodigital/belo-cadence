<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;

it('counts what is due today and in the coming week', function (): void {
    NotificationSchedule::factory()
        ->for(Client::factory())
        ->create(['next_send_at' => CarbonImmutable::now()->addHours(2)]);

    NotificationSchedule::factory()
        ->for(Client::factory())
        ->create(['next_send_at' => CarbonImmutable::now()->addDays(3)]);

    NotificationSchedule::factory()
        ->for(Client::factory())
        ->create(['next_send_at' => CarbonImmutable::now()->addDays(40)]);

    actingAs(administrator())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.metrics.due_today'))
        ->assertSee(__('dashboard.metrics.due_this_week'));
});

it('shows the next notifications and recent failures', function (): void {
    $client = Client::factory()->create(['name' => 'Northwind Studio']);

    NotificationSchedule::factory()
        ->for($client)
        ->create(['next_send_at' => CarbonImmutable::now()->addDay()]);

    NotificationDelivery::factory()
        ->for(Client::factory()->create(['name' => 'Harbour & Pine']))
        ->failed()
        ->create(['subject' => 'Failed annual reminder']);

    actingAs(administrator())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Northwind Studio')
        ->assertSee('Failed annual reminder');
});

it('celebrates an empty failure list', function (): void {
    actingAs(administrator())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.panels.failures_empty'));
});

it('leaves out panels the reader may not see', function (): void {
    NotificationDelivery::factory()->failed()->create(['subject' => 'Failed annual reminder']);

    actingAs(userWithPermissions([PermissionName::DashboardView]))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Failed annual reminder')
        ->assertDontSee(__('dashboard.metrics.sent_this_month'))
        ->assertDontSee(__('dashboard.metrics.due_today'));
});

it('counts a month in the timezone of the person looking', function (): void {
    // Just after midnight on the first of the month in Tokyo is still last month in UTC.
    $tokyoStartOfMonth = CarbonImmutable::now('Asia/Tokyo')->startOfMonth()->addMinutes(30);

    NotificationDelivery::factory()->sent()->create([
        'sent_at' => $tokyoStartOfMonth->setTimezone('UTC'),
        'attempted_at' => $tokyoStartOfMonth->setTimezone('UTC'),
    ]);

    actingAs(administrator(['timezone' => 'Asia/Tokyo']))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.metrics.sent_this_month'));
});
