<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Everything a fresh environment needs, and nothing more.
 *
 * This seeder is safe to run against a real installation: it creates no clients,
 * schedules, deliveries, notifications or demo users. Demo data lives in DemoSeeder
 * and is never called from here.
 */
final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            InitialAdministratorSeeder::class,
            ApplicationSettingsSeeder::class,
        ]);
    }
}
