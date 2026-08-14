<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use App\Models\DefaultClientNotification;
use Illuminate\Database\Seeder;

/**
 * The notification defaults offered on the client creation form.
 */
final class DemoDefaultClientNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [ClientEmailTemplate::MonthlyReminder, ClientNotificationFrequency::Monthly, true],
            [ClientEmailTemplate::AnnualReminder, ClientNotificationFrequency::Yearly, true],
            [ClientEmailTemplate::GeneralReminder, ClientNotificationFrequency::OneTime, false],
        ];

        foreach ($defaults as [$template, $frequency, $enabled]) {
            DefaultClientNotification::query()->updateOrCreate(
                ['template' => $template, 'frequency' => $frequency],
                ['is_enabled_by_default' => $enabled],
            );
        }
    }
}
