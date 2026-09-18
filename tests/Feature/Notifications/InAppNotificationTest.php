<?php

declare(strict_types=1);

use App\Actions\Notifications\ProcessDueNotificationsAction;
use App\Enums\PermissionName;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\User;
use App\Notifications\NotificationDeliveryFailedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

function notifyOfFailure(User $user): void
{
    $delivery = NotificationDelivery::factory()->failed()->create();

    $user->notify(new NotificationDeliveryFailedNotification($delivery));
}

describe('the notification bell', function (): void {
    it('shows nothing to report when there are no notifications', function (): void {
        actingAs(administrator())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('notifications.empty'));
    });

    it('shows an unread count', function (): void {
        $user = administrator();

        notifyOfFailure($user);
        notifyOfFailure($user);

        actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('notifications.unread_count', ['count' => 2]))
            ->assertSee(__('notifications.delivery_failed.title'));
    });
});

describe('the notifications page', function (): void {
    it('lists the notifications a person received', function (): void {
        $user = administrator();
        notifyOfFailure($user);

        actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('notifications.delivery_failed.title'));
    });

    it('marks one notification as read and follows its link', function (): void {
        $user = administrator();
        notifyOfFailure($user);

        $notification = $user->notifications()->firstOrFail();

        actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect($notification->data['path']);

        expect($user->fresh()->unreadNotifications()->count())->toBe(0);
    });

    it('stores a path, so a link written by the scheduler works on any host', function (): void {
        $user = administrator();
        notifyOfFailure($user);

        $notification = $user->notifications()->firstOrFail();

        expect($notification->data['path'])->toStartWith('/cadence/deliveries/')
            ->and($notification->data['path'])->not->toContain('http');
    });

    it('ignores a stored path that points somewhere else entirely', function (): void {
        $user = administrator();
        notifyOfFailure($user);

        $notification = $user->notifications()->firstOrFail();
        $notification->update(['data' => [...$notification->data, 'path' => '//evil.test/phish']]);

        actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));
    });

    it('marks every notification as read', function (): void {
        $user = administrator();
        notifyOfFailure($user);
        notifyOfFailure($user);

        actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        expect($user->fresh()->unreadNotifications()->count())->toBe(0)
            ->and($user->fresh()->notifications()->count())->toBe(2);
    });

    it('never lets somebody touch another person notification', function (): void {
        $owner = administrator();
        $other = administrator();

        notifyOfFailure($owner);

        $notification = $owner->notifications()->firstOrFail();

        actingAs($other)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        expect($owner->fresh()->unreadNotifications()->count())->toBe(1);
    });
});

describe('what raises a notification', function (): void {
    it('does not notify anybody about a successful send', function (): void {
        Notification::fake();
        Mail::fake();

        userWithPermissions([PermissionName::NotificationDeliveriesViewAny]);

        NotificationSchedule::factory()
            ->for(Client::factory())
            ->due()
            ->create();

        app(ProcessDueNotificationsAction::class)();

        Notification::assertNothingSent();
    });
});
