<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Notifications\NotificationMailData;
use App\Enums\EmailTemplate;
use App\Enums\EmailTemplateAudience;
use App\Mail\NotificationMail;
use App\Models\ApplicationSettings;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;

/**
 * Renders an email template with an example name, so people can read what a
 * client receives before choosing a template.
 *
 * A preview goes through exactly the same mailable and view as a real send; only the
 * client is invented.
 */
final class EmailTemplatePreview
{
    private const SAMPLE_RECIPIENT = 'client@example.com';

    /**
     * @return array{template: EmailTemplate, subject: string, html: string}
     */
    public function render(EmailTemplate $template): array
    {
        $settings = ApplicationSettings::current();

        // A client template is previewed against an example client, a recipient-list one
        // against an example list, so each reads the way it will actually arrive.
        $targetName = (string) ($template->audience() === EmailTemplateAudience::Client
            ? __('cadence.templates.sample_client')
            : __('cadence.templates.sample_list'));

        $subject = $template->hasOwnCopy()
            ? $template->subject(
                ['application' => $settings->application_name, 'client' => $targetName, 'name' => $targetName],
                $settings->default_locale->value,
            )
            : (string) __('cadence.templates.sample_subject');

        $data = new NotificationMailData(
            template: $template,
            applicationName: $settings->application_name,
            targetName: $targetName,
            recipientEmail: new EmailAddress(self::SAMPLE_RECIPIENT),
            recipientName: $template->audience() === EmailTemplateAudience::Client ? $targetName : null,
            senderEmail: $settings->client_email_sender_email,
            senderName: $settings->client_email_sender_name,
            subject: $subject,
            scheduledForLabel: CarbonImmutable::now($settings->default_timezone->toDateTimeZone())
                ->format((string) __('common.formats.datetime')),
            locale: $settings->default_locale,
            message: $template->hasOwnCopy() ? null : (string) __('cadence.templates.sample_message'),
        );

        return [
            'template' => $template,
            'subject' => $subject,
            'html' => (new NotificationMail($data))->render(),
        ];
    }

    /**
     * Every template in the catalogue, in declaration order.
     *
     * @return list<array{template: EmailTemplate, subject: string, html: string}>
     */
    public function all(): array
    {
        return array_map(
            fn (EmailTemplate $template): array => $this->render($template),
            EmailTemplate::cases(),
        );
    }
}
