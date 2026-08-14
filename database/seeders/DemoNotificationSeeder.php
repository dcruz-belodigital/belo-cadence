<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ClientNotificationDelivery;
use App\Models\User;
use App\Notifications\ClientNotificationDeliveryFailedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

/**
 * Read and unread application notifications, so the bell can be tried out in both states.
 */
final class DemoNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $failedDelivery = ClientNotificationDelivery::query()->failed()->with('client')->first();

        if (! $failedDelivery instanceof ClientNotificationDelivery) {
            return;
        }

        $recipients = User::query()
            ->whereIn('email', ['ada@example.test', 'chidi@example.test'])
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ClientNotificationDeliveryFailedNotification($failedDelivery));

        // One of them has already read theirs.
        $chidi = $recipients->firstWhere('email', 'chidi@example.test');

        $chidi?->unreadNotifications()->update(['read_at' => CarbonImmutable::now()->subHours(2)]);
    }
}
