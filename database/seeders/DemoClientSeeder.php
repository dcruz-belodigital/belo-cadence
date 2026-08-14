<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClientStatus;
use App\Models\Client;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Clearly fictional clients covering every state the interface can show.
 */
final class DemoClientSeeder extends Seeder
{
    /**
     * @var list<array{name: string, email: string, status: ClientStatus, notes: string|null, archived: bool}>
     */
    private const CLIENTS = [
        [
            'name' => 'Northwind Studio',
            'email' => 'hello@northwind.test',
            'status' => ClientStatus::Active,
            'notes' => 'Prefers to be contacted early in the week.',
            'archived' => false,
        ],
        [
            'name' => 'Harbour & Pine',
            'email' => 'accounts@harbourpine.test',
            'status' => ClientStatus::Active,
            'notes' => null,
            'archived' => false,
        ],
        [
            'name' => 'Meridian Collective',
            'email' => 'team@meridian.test',
            'status' => ClientStatus::Active,
            'notes' => 'Two locations; the invoice contact differs from this address.',
            'archived' => false,
        ],
        [
            'name' => 'Quiet Fox Bakery',
            'email' => 'orders@quietfox.test',
            'status' => ClientStatus::Active,
            'notes' => null,
            'archived' => false,
        ],
        [
            'name' => 'Lantern Works',
            'email' => 'info@lanternworks.test',
            'status' => ClientStatus::Inactive,
            'notes' => 'Paused their retainer; do not send scheduled email.',
            'archived' => false,
        ],
        [
            'name' => 'Old Mill Provisions',
            'email' => 'contact@oldmill.test',
            'status' => ClientStatus::Inactive,
            'notes' => null,
            'archived' => false,
        ],
        [
            'name' => 'Copper Lane Archive',
            'email' => 'archive@copperlane.test',
            'status' => ClientStatus::Active,
            'notes' => 'Archived after the project closed. Kept for its history.',
            'archived' => true,
        ],
    ];

    public function run(): void
    {
        foreach (self::CLIENTS as $demoClient) {
            $client = Client::withTrashed()->updateOrCreate(
                ['email' => $demoClient['email']],
                [
                    'name' => $demoClient['name'],
                    'status' => $demoClient['status'],
                    'notes' => $demoClient['notes'],
                    'deleted_at' => $demoClient['archived'] ? CarbonImmutable::now()->subDays(21) : null,
                ],
            );

            $client->save();
        }
    }
}
