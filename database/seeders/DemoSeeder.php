<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * A complete, clearly fictional demonstration environment.
 *
 * Run it explicitly:
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * It is never called from DatabaseSeeder. The essential seeders are invoked first so
 * this works on an empty database as well as on a seeded one; both are idempotent.
 *
 * The real initial administrator is seeded alongside the demo accounts, so a
 * demonstration database is signed into the same way a real installation is. Its
 * password still comes from CADENCE_ADMIN_PASSWORD, or is generated and shown once —
 * demo data is not a reason to hardcode the credential of the account that owns
 * everything.
 */
final class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            InitialAdministratorSeeder::class,
            ApplicationSettingsSeeder::class,

            DemoUserSeeder::class,
            DemoDefaultClientNotificationSeeder::class,
            DemoClientSeeder::class,
            DemoNotificationScheduleSeeder::class,
            DemoInAppNotificationSeeder::class,
            DemoAuditSeeder::class,
        ]);
    }
}
