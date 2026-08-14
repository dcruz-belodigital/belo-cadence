<?php

declare(strict_types=1);

namespace App\Actions\ClientNotifications;

use App\Data\ClientNotifications\ClientNotificationMailData;
use App\Enums\ClientNotificationDeliveryStatus;
use App\Enums\ClientNotificationFrequency;
use App\Enums\PermissionName;
use App\Mail\ClientNotificationMail;
use App\Models\ApplicationSettings;
use App\Models\ClientNotificationDelivery;
use App\Models\ClientNotificationSchedule;
use App\Models\User;
use App\Notifications\ClientNotificationDeliveryFailedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Sends one scheduled occurrence and records exactly what happened.
 *
 * The order of work is deliberate:
 *
 *  1. the message is rendered, so its exact content can be stored even if sending fails;
 *  2. the occurrence is claimed inside a transaction that locks the schedule and
 *     advances it, with a unique index on (schedule, occurrence) as the final word on
 *     duplicates;
 *  3. only then is the email handed to the mailer, outside any transaction, so a
 *     rollback can never erase the record of a message that has already left.
 *
 * A one-time schedule is closed when its occurrence is claimed rather than when the
 * send succeeds. That keeps the outcome deterministic and stops a failing send from
 * being retried endlessly; the failure is visible in history and raises an internal
 * notification instead.
 */
final class SendClientNotificationAction
{
    public function __construct(
        private readonly CalculateNextNotificationDateAction $calculateNextNotificationDate,
    ) {}

    /**
     * Returns the delivery that was recorded, or null when this occurrence had already
     * been handled.
     */
    public function __invoke(
        ClientNotificationSchedule $schedule,
        CarbonImmutable $occurrence,
    ): ?ClientNotificationDelivery {
        $settings = ApplicationSettings::current();
        $client = $schedule->client;

        $mailData = new ClientNotificationMailData(
            template: $schedule->template,
            applicationName: $settings->application_name,
            clientName: $client->name,
            recipientEmail: $client->email,
            recipientName: $client->name,
            senderEmail: $settings->client_email_sender_email,
            senderName: $settings->client_email_sender_name,
            subject: $this->subject($schedule, $settings, $client->name),
            scheduledForLabel: $occurrence
                ->setTimezone($settings->default_timezone->toDateTimeZone())
                ->format((string) __('common.formats.datetime')),
            locale: $settings->default_locale,
        );

        $mail = new ClientNotificationMail($mailData);

        $renderFailure = null;

        try {
            $bodyHtml = $mail->render();
        } catch (Throwable $exception) {
            $bodyHtml = '';
            $renderFailure = $exception->getMessage();

            Log::error('Rendering a client notification failed.', [
                'client_notification_schedule_id' => $schedule->getKey(),
                'exception' => $exception,
            ]);
        }

        $delivery = $this->claimOccurrence($schedule, $occurrence, $mailData, $bodyHtml);

        if (! $delivery instanceof ClientNotificationDelivery) {
            return null;
        }

        if ($renderFailure !== null) {
            return $this->recordFailure($delivery, $renderFailure);
        }

        try {
            Mail::send($mail);
        } catch (Throwable $exception) {
            Log::error('Sending a client notification failed.', [
                'client_notification_delivery_id' => $delivery->getKey(),
                'exception' => $exception,
            ]);

            return $this->recordFailure($delivery, $exception->getMessage());
        }

        return $this->recordSuccess($delivery, $schedule, $occurrence);
    }

    private function subject(
        ClientNotificationSchedule $schedule,
        ApplicationSettings $settings,
        string $clientName,
    ): string {
        return $schedule->template->subject(
            [
                'application' => $settings->application_name,
                'client' => $clientName,
            ],
            $settings->default_locale->value,
        );
    }

    /**
     * Claims the occurrence and moves the schedule on, or returns null when somebody
     * else already did.
     */
    private function claimOccurrence(
        ClientNotificationSchedule $schedule,
        CarbonImmutable $occurrence,
        ClientNotificationMailData $mailData,
        string $bodyHtml,
    ): ?ClientNotificationDelivery {
        try {
            return DB::transaction(function () use ($schedule, $occurrence, $mailData, $bodyHtml): ?ClientNotificationDelivery {
                $locked = ClientNotificationSchedule::query()
                    ->whereKey($schedule->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $locked instanceof ClientNotificationSchedule) {
                    return null;
                }

                $stillDue = $locked->is_enabled
                    && $locked->next_send_at !== null
                    && $locked->next_send_at->equalTo($occurrence);

                if (! $stillDue) {
                    return null;
                }

                $delivery = ClientNotificationDelivery::query()->create([
                    'client_notification_schedule_id' => $locked->getKey(),
                    'client_id' => $locked->client_id,
                    'template' => $mailData->template,
                    'recipient_email' => $mailData->recipientEmail,
                    'recipient_name' => $mailData->recipientName,
                    'sender_email' => $mailData->senderEmail,
                    'sender_name' => $mailData->senderName,
                    'subject' => $mailData->subject,
                    'body_html' => $bodyHtml,
                    'scheduled_for' => $occurrence,
                    'attempted_at' => CarbonImmutable::now(),
                    'status' => ClientNotificationDeliveryStatus::Pending,
                ]);

                $nextSendAt = ($this->calculateNextNotificationDate)(
                    $locked->frequency,
                    $locked->starts_at,
                    $occurrence,
                    ApplicationSettings::current()->default_timezone,
                );

                $locked->update([
                    'next_send_at' => $nextSendAt,
                    'is_enabled' => $locked->frequency === ClientNotificationFrequency::OneTime
                        ? false
                        : $locked->is_enabled,
                ]);

                return $delivery;
            });
        } catch (UniqueConstraintViolationException) {
            // Another process recorded this exact occurrence first.
            return null;
        }
    }

    private function recordSuccess(
        ClientNotificationDelivery $delivery,
        ClientNotificationSchedule $schedule,
        CarbonImmutable $occurrence,
    ): ClientNotificationDelivery {
        DB::transaction(function () use ($delivery, $schedule, $occurrence): void {
            $delivery->update([
                'status' => ClientNotificationDeliveryStatus::Sent,
                'sent_at' => CarbonImmutable::now(),
            ]);

            ClientNotificationSchedule::query()
                ->whereKey($schedule->getKey())
                ->update(['last_sent_at' => $occurrence]);
        });

        return $delivery;
    }

    private function recordFailure(
        ClientNotificationDelivery $delivery,
        string $failureMessage,
    ): ClientNotificationDelivery {
        $delivery->update([
            'status' => ClientNotificationDeliveryStatus::Failed,
            'failure_message' => $failureMessage,
        ]);

        Notification::send(
            $this->usersWatchingDeliveries(),
            new ClientNotificationDeliveryFailedNotification($delivery),
        );

        return $delivery;
    }

    /**
     * The active users who are allowed to see delivery history.
     *
     * Permissions are only ever granted through roles, so the roles are queried
     * directly instead of relying on a permission record existing.
     *
     * @return Collection<int, User>
     */
    private function usersWatchingDeliveries(): Collection
    {
        return User::query()
            ->active()
            ->whereHas('roles.permissions', fn (Builder $permissions): Builder => $permissions
                ->where('name', PermissionName::NotificationDeliveriesViewAny->value))
            ->get();
    }
}
