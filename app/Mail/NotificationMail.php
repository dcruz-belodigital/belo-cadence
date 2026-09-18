<?php

declare(strict_types=1);

namespace App\Mail;

use App\Data\Notifications\NotificationMailData;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Renders one of the source-controlled email templates.
 *
 * The template is chosen by the schedule, or by the person sending by hand; everything
 * the view prints has already been decided before it gets here.
 */
final class NotificationMail extends Mailable
{
    public function __construct(
        public readonly NotificationMailData $data,
    ) {
        $this->locale($data->locale->value);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->data->senderEmail->value, $this->data->senderName),
            to: [new Address($this->data->recipientEmail->value, $this->data->recipientName)],
            subject: $this->data->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->data->template->view(),
            with: ['data' => $this->data],
        );
    }
}
