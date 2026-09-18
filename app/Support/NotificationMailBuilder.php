<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Notifications\NotificationDispatch;
use App\Data\Notifications\NotificationMailData;
use App\Data\Notifications\PreparedNotificationMail;
use App\Mail\NotificationMail;
use App\Models\ApplicationSettings;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns a dispatch into the exact message one recipient will receive.
 *
 * Every send goes through here, so the sender, the locale, the subject and the
 * scheduled-for line are decided in one place rather than once per sending path.
 */
final class NotificationMailBuilder
{
    /**
     * Builds and renders the message for one address.
     */
    public function prepare(
        NotificationDispatch $dispatch,
        EmailAddress $recipient,
        CarbonImmutable $occurrence,
    ): PreparedNotificationMail {
        $data = $this->build($dispatch, $recipient, $occurrence);
        $mail = new NotificationMail($data);

        try {
            return new PreparedNotificationMail($data, $mail, $mail->render());
        } catch (Throwable $exception) {
            Log::error('Rendering a notification failed.', [
                'template' => $dispatch->template->value,
                'recipient' => $recipient->value,
                'exception' => $exception,
            ]);

            return new PreparedNotificationMail($data, $mail, '', $exception->getMessage());
        }
    }

    private function build(
        NotificationDispatch $dispatch,
        EmailAddress $recipient,
        CarbonImmutable $occurrence,
    ): NotificationMailData {
        $settings = ApplicationSettings::current();

        return new NotificationMailData(
            template: $dispatch->template,
            applicationName: $settings->application_name,
            targetName: $dispatch->targetName,
            recipientEmail: $recipient,
            recipientName: $dispatch->recipientName,
            senderEmail: $settings->client_email_sender_email,
            senderName: $settings->client_email_sender_name,
            subject: $this->subject($dispatch, $settings),
            scheduledForLabel: $occurrence
                ->setTimezone($settings->default_timezone->toDateTimeZone())
                ->format((string) __('common.formats.datetime')),
            locale: $settings->default_locale,
            message: $dispatch->template->hasOwnCopy() ? null : $dispatch->message,
        );
    }

    /**
     * The blank template has no subject of its own, so it uses the one written on the
     * schedule; every other template's subject is source-controlled copy.
     */
    private function subject(NotificationDispatch $dispatch, ApplicationSettings $settings): string
    {
        if (! $dispatch->template->hasOwnCopy()) {
            return (string) $dispatch->subject;
        }

        $name = $dispatch->targetName ?? '';

        return $dispatch->template->subject(
            [
                'application' => $settings->application_name,
                'client' => $name,
                'name' => $name,
            ],
            $settings->default_locale->value,
        );
    }
}
