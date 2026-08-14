<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\ClientNotifications\ClientNotificationMailData;
use App\Enums\ClientEmailTemplate;
use App\Mail\ClientNotificationMail;
use App\Models\ApplicationSettings;
use App\ValueObjects\EmailAddress;
use Carbon\CarbonImmutable;

/**
 * Renders a client email template with an example client, so people can read what a
 * client receives before choosing a template.
 *
 * A preview goes through exactly the same mailable and view as a real send; only the
 * client is invented.
 */
final class ClientEmailTemplatePreview
{
    private const SAMPLE_RECIPIENT = 'client@example.com';

    /**
     * @return array{template: ClientEmailTemplate, subject: string, html: string}
     */
    public function render(ClientEmailTemplate $template): array
    {
        $settings = ApplicationSettings::current();
        $clientName = (string) __('cadence.templates.sample_client');

        $subject = $template->subject(
            ['application' => $settings->application_name, 'client' => $clientName],
            $settings->default_locale->value,
        );

        $data = new ClientNotificationMailData(
            template: $template,
            applicationName: $settings->application_name,
            clientName: $clientName,
            recipientEmail: new EmailAddress(self::SAMPLE_RECIPIENT),
            recipientName: $clientName,
            senderEmail: $settings->client_email_sender_email,
            senderName: $settings->client_email_sender_name,
            subject: $subject,
            scheduledForLabel: CarbonImmutable::now($settings->default_timezone->toDateTimeZone())
                ->format((string) __('common.formats.datetime')),
            locale: $settings->default_locale,
        );

        return [
            'template' => $template,
            'subject' => $subject,
            'html' => (new ClientNotificationMail($data))->render(),
        ];
    }

    /**
     * Every template in the catalogue, in declaration order.
     *
     * @return list<array{template: ClientEmailTemplate, subject: string, html: string}>
     */
    public function all(): array
    {
        return array_map(
            fn (ClientEmailTemplate $template): array => $this->render($template),
            ClientEmailTemplate::cases(),
        );
    }
}
