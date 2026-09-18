<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EmailTemplate;
use App\Enums\NotificationFrequency;
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
            [EmailTemplate::MonthlyReminder, NotificationFrequency::Monthly, true],
            [EmailTemplate::AnnualReminder, NotificationFrequency::Yearly, true],
            [EmailTemplate::GeneralReminder, NotificationFrequency::OneTime, false],
        ];

        foreach ($defaults as [$template, $frequency, $enabled]) {
            DefaultClientNotification::query()->updateOrCreate(
                ['template' => $template, 'frequency' => $frequency],
                ['is_enabled_by_default' => $enabled],
            );
        }
    }
}
