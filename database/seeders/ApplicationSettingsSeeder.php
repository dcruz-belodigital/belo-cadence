<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApplicationSettings;
use Illuminate\Database\Seeder;

/**
 * Creates the single application settings row from the configured defaults.
 */
final class ApplicationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (ApplicationSettings::query()->exists()) {
            return;
        }

        ApplicationSettings::defaults()->save();
    }
}
