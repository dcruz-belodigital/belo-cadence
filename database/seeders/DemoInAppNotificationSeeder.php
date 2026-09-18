<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\NotificationDeliveryFailedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

/**
 * Read and unread application notifications, so the bell can be tried out in both states.
 */
final class DemoInAppNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $failedDelivery = NotificationDelivery::query()->failed()->with('client')->first();

        if (! $failedDelivery instanceof NotificationDelivery) {
            return;
        }

        $recipients = User::query()
            ->whereIn('email', ['ada@example.test', 'chidi@example.test'])
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NotificationDeliveryFailedNotification($failedDelivery));

        // One of them has already read theirs.
        $chidi = $recipients->firstWhere('email', 'chidi@example.test');

        $chidi?->unreadNotifications()->update(['read_at' => CarbonImmutable::now()->subHours(2)]);
    }
}
