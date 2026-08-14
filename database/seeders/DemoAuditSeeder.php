<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AuditAction;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientNotificationSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * A short audit history, including one entry performed by the system itself.
 */
final class DemoAuditSeeder extends Seeder
{
    public function run(): void
    {
        if (Audit::query()->exists()) {
            return;
        }

        $administrator = User::query()->where('email', 'ada@example.test')->first();
        $coordinator = User::query()->where('email', 'bruno@example.test')->first();
        $client = Client::query()->where('email', 'hello@northwind.test')->first();
        $schedule = ClientNotificationSchedule::query()->where('client_id', $client?->getKey())->first();

        if (! $administrator instanceof User || ! $client instanceof Client) {
            return;
        }

        $now = CarbonImmutable::now();

        Audit::query()->create([
            'user_id' => $administrator->getKey(),
            'action' => AuditAction::ClientCreated,
            'auditable_type' => $client->getMorphClass(),
            'auditable_id' => $client->getKey(),
            'new_values' => [
                'name' => $client->name,
                'email' => $client->email->value,
                'status' => $client->status->value,
            ],
            'created_at' => $now->subDays(30),
        ]);

        Audit::query()->create([
            'user_id' => $coordinator?->getKey(),
            'action' => AuditAction::ClientUpdated,
            'auditable_type' => $client->getMorphClass(),
            'auditable_id' => $client->getKey(),
            'old_values' => ['notes' => null],
            'new_values' => ['notes' => $client->notes],
            'created_at' => $now->subDays(12),
        ]);

        if ($schedule instanceof ClientNotificationSchedule) {
            Audit::query()->create([
                'user_id' => $coordinator?->getKey(),
                'action' => AuditAction::ClientNotificationScheduleCreated,
                'auditable_type' => $schedule->getMorphClass(),
                'auditable_id' => $schedule->getKey(),
                'new_values' => [
                    'template' => $schedule->template->value,
                    'frequency' => $schedule->frequency->value,
                ],
                'created_at' => $now->subDays(12),
            ]);
        }

        Audit::query()->create([
            'user_id' => $administrator->getKey(),
            'action' => AuditAction::ApplicationSettingsUpdated,
            'old_values' => ['client_email_sender_name' => 'Belo'],
            'new_values' => ['client_email_sender_name' => 'Belo Cadence'],
            'created_at' => $now->subDays(6),
        ]);

        // Imports performed by a scheduled process have no signed-in user.
        Audit::query()->create([
            'user_id' => null,
            'action' => AuditAction::ClientsImported,
            'metadata' => ['created' => 4, 'updated' => 1],
            'created_at' => $now->subDays(3),
        ]);
    }
}
