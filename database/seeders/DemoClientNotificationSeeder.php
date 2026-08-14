<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationDeliveryStatus;
use App\Enums\ClientNotificationFrequency;
use App\Models\Client;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Schedules and delivery history covering every scheduling and sending state:
 * due today, due soon, further out, disabled, completed one-time, sent, failed and
 * pending.
 */
final class DemoClientNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now();

        $northwind = $this->client('hello@northwind.test');
        $harbour = $this->client('accounts@harbourpine.test');
        $meridian = $this->client('team@meridian.test');
        $quietFox = $this->client('orders@quietfox.test');
        $lantern = $this->client('info@lanternworks.test');
        $archived = Client::withTrashed()->where('email', 'archive@copperlane.test')->firstOrFail();

        // Due today, and already overdue by a few minutes.
        $dueToday = $this->schedule($northwind, ClientEmailTemplate::MonthlyReminder, ClientNotificationFrequency::Monthly, $now->subMinutes(10), true);

        // Due later today.
        $this->schedule($harbour, ClientEmailTemplate::GeneralReminder, ClientNotificationFrequency::OneTime, $now->addHours(6), true);

        // Due within the week.
        $this->schedule($meridian, ClientEmailTemplate::MonthlyReminder, ClientNotificationFrequency::Monthly, $now->addDays(4)->setTime(9, 0), true);

        // Anchored on the 31st, to show the end-of-month rule.
        $this->schedule($quietFox, ClientEmailTemplate::MonthlyReminder, ClientNotificationFrequency::Monthly, $now->addMonth()->setDate((int) $now->year, (int) $now->month, 31)->setTime(8, 30), true);

        // Further out.
        $annual = $this->schedule($northwind, ClientEmailTemplate::AnnualReminder, ClientNotificationFrequency::Yearly, $now->addMonths(5)->setTime(10, 0), true);

        // Disabled, keeping its anchor.
        $disabled = ClientNotificationSchedule::query()->updateOrCreate(
            ['client_id' => $harbour->getKey(), 'template' => ClientEmailTemplate::AnnualReminder, 'frequency' => ClientNotificationFrequency::Yearly],
            [
                'starts_at' => $now->addMonths(2)->setTime(9, 0),
                'next_send_at' => null,
                'is_enabled' => false,
            ],
        );

        // A one-time notification that has already been sent and closed itself.
        $completed = ClientNotificationSchedule::query()->updateOrCreate(
            ['client_id' => $meridian->getKey(), 'template' => ClientEmailTemplate::GeneralReminder, 'frequency' => ClientNotificationFrequency::OneTime],
            [
                'starts_at' => $now->subDays(20)->setTime(9, 0),
                'next_send_at' => null,
                'last_sent_at' => $now->subDays(20)->setTime(9, 0),
                'is_enabled' => false,
            ],
        );

        // An inactive client keeps its configuration but is never sent to.
        $this->schedule($lantern, ClientEmailTemplate::MonthlyReminder, ClientNotificationFrequency::Monthly, $now->addDays(9)->setTime(9, 0), true);

        // The archived client's schedule is switched off, as archiving does.
        ClientNotificationSchedule::query()->updateOrCreate(
            ['client_id' => $archived->getKey(), 'template' => ClientEmailTemplate::MonthlyReminder, 'frequency' => ClientNotificationFrequency::Monthly],
            [
                'starts_at' => $now->subMonths(6)->setTime(9, 0),
                'next_send_at' => null,
                'last_sent_at' => $now->subMonths(2)->setTime(9, 0),
                'is_enabled' => false,
            ],
        );

        $this->history($dueToday, $now);
        $this->history($annual, $now);
        $this->completedHistory($completed);
        $this->failedHistory($disabled, $now);
        $this->pendingHistory($dueToday, $now);
    }

    private function client(string $email): Client
    {
        return Client::query()->where('email', $email)->firstOrFail();
    }

    private function schedule(
        Client $client,
        ClientEmailTemplate $template,
        ClientNotificationFrequency $frequency,
        CarbonImmutable $nextSendAt,
        bool $isEnabled,
    ): ClientNotificationSchedule {
        return ClientNotificationSchedule::query()->updateOrCreate(
            ['client_id' => $client->getKey(), 'template' => $template, 'frequency' => $frequency],
            [
                'starts_at' => $nextSendAt,
                'next_send_at' => $nextSendAt,
                'is_enabled' => $isEnabled,
            ],
        );
    }

    /**
     * Three successful sends in the recent past.
     */
    private function history(ClientNotificationSchedule $schedule, CarbonImmutable $now): void
    {
        foreach ([1, 2, 3] as $monthsAgo) {
            $occurrence = $now->subMonths($monthsAgo)->setTime(9, 0);

            $this->delivery($schedule, $occurrence, ClientNotificationDeliveryStatus::Sent);
        }

        $schedule->update(['last_sent_at' => $now->subMonth()->setTime(9, 0)]);
    }

    private function completedHistory(ClientNotificationSchedule $schedule): void
    {
        $this->delivery($schedule, $schedule->last_sent_at, ClientNotificationDeliveryStatus::Sent);
    }

    private function failedHistory(ClientNotificationSchedule $schedule, CarbonImmutable $now): void
    {
        $this->delivery(
            $schedule,
            $now->subDays(5)->setTime(9, 0),
            ClientNotificationDeliveryStatus::Failed,
            'Connection could not be established with host smtp.example.test.',
        );
    }

    private function pendingHistory(ClientNotificationSchedule $schedule, CarbonImmutable $now): void
    {
        $this->delivery($schedule, $now->subMonths(4)->setTime(9, 0), ClientNotificationDeliveryStatus::Pending);
    }

    private function delivery(
        ClientNotificationSchedule $schedule,
        CarbonImmutable $occurrence,
        ClientNotificationDeliveryStatus $status,
        ?string $failureMessage = null,
    ): void {
        $client = Client::withTrashed()->findOrFail($schedule->client_id);

        ClientNotificationDelivery::query()->updateOrCreate(
            [
                'client_notification_schedule_id' => $schedule->getKey(),
                'scheduled_for' => $occurrence,
            ],
            [
                'client_id' => $client->getKey(),
                'template' => $schedule->template,
                'recipient_email' => $client->email,
                'recipient_name' => $client->name,
                'sender_email' => 'cadence@belo-cadence.test',
                'sender_name' => 'Belo Cadence',
                'subject' => $schedule->template->subject(['application' => 'Belo Cadence', 'client' => $client->name]),
                'body_html' => $this->demoBody($client->name),
                'attempted_at' => $occurrence->addMinute(),
                'sent_at' => $status === ClientNotificationDeliveryStatus::Sent ? $occurrence->addMinute() : null,
                'status' => $status,
                'failure_message' => $failureMessage,
            ],
        );
    }

    private function demoBody(string $clientName): string
    {
        return <<<HTML
        <html lang="en"><body style="font-family:sans-serif;">
        <p>Hello {$clientName},</p>
        <p>This is a demonstration snapshot of a message that was produced for one scheduled occurrence.</p>
        <p>Kind regards,<br>Belo Cadence</p>
        </body></html>
        HTML;
    }
}
