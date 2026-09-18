<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EmailTemplate;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationFrequency;
use App\Models\Client;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Schedules and delivery history covering every scheduling and sending state:
 * due today, due soon, further out, disabled, completed one-time, sent, failed,
 * pending, one email a person sent by hand outside any schedule, and both kinds of
 * target — schedules that belong to a client and schedules that send to their own
 * named list of addresses, including one using the blank template.
 */
final class DemoNotificationScheduleSeeder extends Seeder
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
        $dueToday = $this->schedule($northwind, EmailTemplate::MonthlyReminder, NotificationFrequency::Monthly, $now->subMinutes(10), true);

        // Due later today.
        $this->schedule($harbour, EmailTemplate::GeneralReminder, NotificationFrequency::OneTime, $now->addHours(6), true);

        // Due within the week.
        $this->schedule($meridian, EmailTemplate::MonthlyReminder, NotificationFrequency::Monthly, $now->addDays(4)->setTime(9, 0), true);

        // Anchored on the 31st, to show the end-of-month rule.
        $this->schedule($quietFox, EmailTemplate::MonthlyReminder, NotificationFrequency::Monthly, $now->addMonth()->setDate((int) $now->year, (int) $now->month, 31)->setTime(8, 30), true);

        // Further out.
        $annual = $this->schedule($northwind, EmailTemplate::AnnualReminder, NotificationFrequency::Yearly, $now->addMonths(5)->setTime(10, 0), true);

        // Disabled, keeping its anchor.
        $disabled = NotificationSchedule::query()->updateOrCreate(
            ['client_id' => $harbour->getKey(), 'template' => EmailTemplate::AnnualReminder, 'frequency' => NotificationFrequency::Yearly],
            [
                'starts_at' => $now->addMonths(2)->setTime(9, 0),
                'next_send_at' => null,
                'is_enabled' => false,
            ],
        );

        // A one-time notification that has already been sent and closed itself.
        $completed = NotificationSchedule::query()->updateOrCreate(
            ['client_id' => $meridian->getKey(), 'template' => EmailTemplate::GeneralReminder, 'frequency' => NotificationFrequency::OneTime],
            [
                'starts_at' => $now->subDays(20)->setTime(9, 0),
                'next_send_at' => null,
                'last_sent_at' => $now->subDays(20)->setTime(9, 0),
                'is_enabled' => false,
            ],
        );

        // An inactive client keeps its configuration but is never sent to.
        $this->schedule($lantern, EmailTemplate::MonthlyReminder, NotificationFrequency::Monthly, $now->addDays(9)->setTime(9, 0), true);

        // The archived client's schedule is switched off, as archiving does.
        NotificationSchedule::query()->updateOrCreate(
            ['client_id' => $archived->getKey(), 'template' => EmailTemplate::MonthlyReminder, 'frequency' => NotificationFrequency::Monthly],
            [
                'starts_at' => $now->subMonths(6)->setTime(9, 0),
                'next_send_at' => null,
                'last_sent_at' => $now->subMonths(2)->setTime(9, 0),
                'is_enabled' => false,
            ],
        );

        // A recipient list, which belongs to no client at all.
        $opsDigest = NotificationSchedule::query()->updateOrCreate(
            ['client_id' => null, 'name' => 'Weekly ops digest'],
            [
                'template' => EmailTemplate::StatusUpdate,
                'frequency' => NotificationFrequency::Monthly,
                'recipients' => ['ops@belo-cadence.test', 'finance@belo-cadence.test'],
                'starts_at' => $now->addDays(2)->setTime(8, 0),
                'next_send_at' => $now->addDays(2)->setTime(8, 0),
                'is_enabled' => true,
            ],
        );

        // The blank template, whose wording lives on the schedule rather than in the source.
        NotificationSchedule::query()->updateOrCreate(
            ['client_id' => null, 'name' => 'Quarterly board note'],
            [
                'template' => EmailTemplate::Blank,
                'frequency' => NotificationFrequency::Yearly,
                'recipients' => ['board@belo-cadence.test'],
                'subject' => 'Quarterly figures are ready',
                'message' => "Hello all,\n\nThe quarterly figures are in the shared drive.\n\nBring any questions to Thursday's call.",
                'starts_at' => $now->addMonths(3)->setTime(9, 0),
                'next_send_at' => $now->addMonths(3)->setTime(9, 0),
                'is_enabled' => true,
            ],
        );

        $this->history($dueToday, $now);
        $this->history($annual, $now);
        $this->completedHistory($completed);
        $this->failedHistory($disabled, $now);
        $this->pendingHistory($dueToday, $now);
        $this->manualHistory($harbour, $now);
        $this->recipientListHistory($opsDigest, $now);
    }

    private function client(string $email): Client
    {
        return Client::query()->where('email', $email)->firstOrFail();
    }

    private function schedule(
        Client $client,
        EmailTemplate $template,
        NotificationFrequency $frequency,
        CarbonImmutable $nextSendAt,
        bool $isEnabled,
    ): NotificationSchedule {
        return NotificationSchedule::query()->updateOrCreate(
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
    private function history(NotificationSchedule $schedule, CarbonImmutable $now): void
    {
        foreach ([1, 2, 3] as $monthsAgo) {
            $occurrence = $now->subMonths($monthsAgo)->setTime(9, 0);

            $this->delivery($schedule, $occurrence, NotificationDeliveryStatus::Sent);
        }

        $schedule->update(['last_sent_at' => $now->subMonth()->setTime(9, 0)]);
    }

    private function completedHistory(NotificationSchedule $schedule): void
    {
        $this->delivery($schedule, $schedule->last_sent_at, NotificationDeliveryStatus::Sent);
    }

    private function failedHistory(NotificationSchedule $schedule, CarbonImmutable $now): void
    {
        $this->delivery(
            $schedule,
            $now->subDays(5)->setTime(9, 0),
            NotificationDeliveryStatus::Failed,
            'Connection could not be established with host smtp.example.test.',
        );
    }

    private function pendingHistory(NotificationSchedule $schedule, CarbonImmutable $now): void
    {
        $this->delivery($schedule, $now->subMonths(4)->setTime(9, 0), NotificationDeliveryStatus::Pending);
    }

    /**
     * One occurrence of a list schedule: one delivery per address, all sharing the
     * occurrence and the name the list had at the time.
     */
    private function recipientListHistory(NotificationSchedule $schedule, CarbonImmutable $now): void
    {
        $occurrence = $now->subDays(9)->setTime(8, 0);

        foreach ($schedule->recipients ?? [] as $index => $recipient) {
            NotificationDelivery::query()->updateOrCreate(
                [
                    'notification_schedule_id' => $schedule->getKey(),
                    'scheduled_for' => $occurrence,
                    'recipient_email' => $recipient->value,
                ],
                [
                    'client_id' => null,
                    'target_name' => $schedule->name,
                    'template' => $schedule->template,
                    'recipient_name' => null,
                    'sender_email' => 'cadence@belo-cadence.test',
                    'sender_name' => 'Belo Cadence',
                    'subject' => $schedule->template->subject(
                        ['application' => 'Belo Cadence', 'name' => (string) $schedule->name],
                    ),
                    'body_html' => $this->demoBody((string) $schedule->name),
                    'attempted_at' => $occurrence->addMinute(),
                    // One address on the list was wrong, which is the case the per-recipient
                    // record exists for: the others still went out.
                    'sent_at' => $index === 1 ? null : $occurrence->addMinute(),
                    'status' => $index === 1
                        ? NotificationDeliveryStatus::Failed
                        : NotificationDeliveryStatus::Sent,
                    'failure_message' => $index === 1
                        ? 'Mailbox unavailable: the address was rejected by the receiving server.'
                        : null,
                ],
            );
        }

        $schedule->update(['last_sent_at' => $occurrence]);
    }

    /**
     * One email somebody sent by hand: no schedule behind it, and a person in front.
     */
    private function manualHistory(Client $client, CarbonImmutable $now): void
    {
        $sentAt = $now->subDays(2)->setTime(14, 25);
        $sender = User::query()->where('email', 'ada@example.test')->first();
        $template = EmailTemplate::GeneralReminder;

        NotificationDelivery::query()->updateOrCreate(
            [
                'client_id' => $client->getKey(),
                'is_manual' => true,
                'template' => $template,
            ],
            [
                'notification_schedule_id' => null,
                'triggered_by_user_id' => $sender?->getKey(),
                'recipient_email' => $client->email,
                'recipient_name' => $client->name,
                'sender_email' => 'cadence@belo-cadence.test',
                'sender_name' => 'Belo Cadence',
                'subject' => $template->subject(['application' => 'Belo Cadence', 'client' => $client->name]),
                'body_html' => $this->demoBody($client->name),
                'scheduled_for' => $sentAt,
                'attempted_at' => $sentAt,
                'sent_at' => $sentAt,
                'status' => NotificationDeliveryStatus::Sent,
                'failure_message' => null,
            ],
        );
    }

    private function delivery(
        NotificationSchedule $schedule,
        CarbonImmutable $occurrence,
        NotificationDeliveryStatus $status,
        ?string $failureMessage = null,
    ): void {
        $client = Client::withTrashed()->findOrFail($schedule->client_id);

        NotificationDelivery::query()->updateOrCreate(
            [
                'notification_schedule_id' => $schedule->getKey(),
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
                'sent_at' => $status === NotificationDeliveryStatus::Sent ? $occurrence->addMinute() : null,
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
