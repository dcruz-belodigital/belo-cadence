<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;

use function Pest\Laravel\actingAs;

/**
 * An archived client has to stay reachable, because restoring it starts from its own
 * page. Editing it, archiving it again or giving it a new schedule must not.
 */
it('opens an archived client from the archived list', function (): void {
    $client = Client::factory()->archived()->create(['name' => 'Copper Lane Archive']);

    $administrator = administrator();

    $listing = actingAs($administrator)
        ->get(route('clients.index', ['archived' => 1]))
        ->assertOk()
        ->assertSee('Copper Lane Archive');

    // The listing must not offer a link that leads nowhere.
    expect($listing->getContent())->toContain(route('clients.show', $client, absolute: false));

    actingAs($administrator)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertSee('Copper Lane Archive')
        ->assertSee(__('clients.show.archived_notice'))
        ->assertSee(__('clients.actions.restore'));
});

it('restores an archived client from its own page', function (): void {
    $client = Client::factory()->archived()->create();
    $schedule = ClientNotificationSchedule::factory()->for($client)->disabled()->create();

    $administrator = administrator();

    actingAs($administrator)
        ->post(route('clients.restore', $client))
        ->assertRedirect(route('clients.show', $client))
        ->assertSessionHas('success');

    expect($client->fresh()->trashed())->toBeFalse()
        ->and($schedule->fresh()->is_enabled)->toBeFalse();

    actingAs($administrator)
        ->get(route('clients.show', $client))
        ->assertOk()
        ->assertDontSee(__('clients.show.archived_notice'));
});

it('shows the history of an archived client without offering changes', function (): void {
    $client = Client::factory()->create();
    $schedule = ClientNotificationSchedule::factory()->for($client)->create();
    $delivery = ClientNotificationDelivery::factory()->forSchedule($schedule)->create(['subject' => 'Past message']);

    $administrator = administrator();

    actingAs($administrator)->delete(route('clients.destroy', $client));

    $page = actingAs($administrator)->get(route('clients.show', $client))->assertOk();

    expect($page->getContent())
        ->toContain('Past message')
        ->not->toContain(route('clients.edit', $client, absolute: false))
        ->not->toContain(route('clients.schedules.create', $client, absolute: false));
});

it('refuses to edit, archive again or add a schedule to an archived client', function (): void {
    $client = Client::factory()->archived()->create();
    $administrator = administrator();

    actingAs($administrator)->get(route('clients.edit', $client))->assertNotFound();
    actingAs($administrator)->put(route('clients.update', $client), [])->assertNotFound();
    actingAs($administrator)->delete(route('clients.destroy', $client))->assertNotFound();
    actingAs($administrator)->get(route('clients.schedules.create', $client))->assertNotFound();
});

it('refuses to restore without the permission', function (): void {
    $client = Client::factory()->archived()->create();

    actingAs(administratorWithout([PermissionName::ClientsDelete]))
        ->post(route('clients.restore', $client))
        ->assertForbidden();

    expect($client->fresh()->trashed())->toBeTrue();
});
