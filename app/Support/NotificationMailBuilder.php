<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Notifications\NotificationAttachment;
use App\Data\Notifications\NotificationDispatch;
use App\Data\Notifications\NotificationMailData;
use App\Data\Notifications\PreparedNotificationMail;
use App\Exceptions\UnresolvedAttachment;
use App\Exceptions\UnresolvedTemplateSlot;
use App\Mail\NotificationMail;
use App\Models\ApplicationSettings;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use App\Models\ClientAttributeValue;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\StoredFile;
use App\ValueObjects\TemplateBinding;
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
     *
     * What the client provides is gathered first and on its own — the blanks the template
     * leaves, and the files it attaches — because failing to find one of those is a
     * different kind of failure from a view that will not render, and it has to be
     * recorded rather than thrown so the delivery still says what happened.
     */
    public function prepare(
        NotificationDispatch $dispatch,
        EmailAddress $recipient,
        CarbonImmutable $occurrence,
    ): PreparedNotificationMail {
        // One lookup for both: the blanks and the attachments read the same answers.
        $client = $dispatch->clientId === null
            ? null
            : Client::query()->withTrashed()->with('attributeValues.attribute')->find($dispatch->clientId);

        try {
            $slotValues = $this->slotValues($dispatch, $client);
            $attachments = $this->attachments($dispatch, $client);
        } catch (UnresolvedTemplateSlot|UnresolvedAttachment $exception) {
            $data = $this->build($dispatch, $recipient, $occurrence, []);

            return new PreparedNotificationMail($data, new NotificationMail($data), '', $exception->getMessage());
        }

        $data = $this->build($dispatch, $recipient, $occurrence, $slotValues, $attachments);
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

    /**
     * @param  array<string, string>  $slotValues
     * @param  list<NotificationAttachment>  $attachments
     */
    private function build(
        NotificationDispatch $dispatch,
        EmailAddress $recipient,
        CarbonImmutable $occurrence,
        array $slotValues,
        array $attachments = [],
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
            slotValues: $slotValues,
            attachments: $attachments,
        );
    }

    /**
     * Fills in every blank the template leaves.
     *
     * A slot bound to a literal takes it as typed. A slot bound to a client attribute
     * reads that client's answer, following the path into a repeater when there is one —
     * which is what makes "use Contacts, field Name" a list of names, and a whole
     * repeater a block of rows.
     *
     * A required blank that cannot be filled stops the send rather than quietly
     * producing an email with a hole in it.
     *
     * @return array<string, string>
     */
    private function slotValues(NotificationDispatch $dispatch, ?Client $client): array
    {
        $slots = TemplateSlots::for($dispatch->template, $dispatch->message);

        if ($slots === []) {
            return [];
        }

        $values = [];

        foreach ($slots as $slot) {
            $binding = $dispatch->templateBindings->for($slot->key);

            if ($binding === null) {
                // Nothing was promised for this blank, so nothing is owed.
                if ($slot->isRequired) {
                    throw UnresolvedTemplateSlot::for($slot->label($dispatch->template));
                }

                $values[$slot->key] = '';

                continue;
            }

            if ($binding->isManual()) {
                $values[$slot->key] = (string) ($binding->value ?? '');

                continue;
            }

            $answered = $this->answerFor($binding, $client);

            // A promise was made and cannot be kept: the client has no answer, or the
            // attribute has since been retired or deleted. Better a recorded failure
            // than an email with a hole in it.
            if ($answered === null) {
                throw UnresolvedTemplateSlot::for($slot->label($dispatch->template));
            }

            $values[$slot->key] = $answered;
        }

        return $values;
    }

    /**
     * The files this notification attaches, in the order they were chosen.
     *
     * A binding is followed exactly as a template value's is, so "the Document field of
     * the Certificates rows" attaches one file per row. What comes back is a reference,
     * and the row it names is loaded and checked against the client it is being sent for
     * — an answer edited by hand cannot make an email carry somebody else's file.
     *
     * A binding that finds nothing stops the send. Somebody chose to attach that file;
     * an email that quietly arrived without it would be worse than a recorded failure.
     *
     * @return list<NotificationAttachment>
     */
    private function attachments(NotificationDispatch $dispatch, ?Client $client): array
    {
        if ($dispatch->attachments->isEmpty()) {
            return [];
        }

        $attachments = [];

        foreach ($dispatch->attachments->bindings as $binding) {
            $answer = $this->answerTo($binding, $client);

            $references = $answer instanceof ClientAttributeValue
                ? StoredFile::allIn($answer->attribute->valuesAt($answer->value, $binding->path))
                : [];

            $files = $references === [] ? [] : ClientAttributeFile::query()
                ->where('client_id', $dispatch->clientId)
                ->whereKey(array_map(static fn (StoredFile $file): int => $file->id, $references))
                ->get()
                ->all();

            if ($files === []) {
                throw UnresolvedAttachment::for($this->attachmentLabel($binding));
            }

            foreach ($files as $file) {
                $attachments[] = NotificationAttachment::fromFile($file);
            }
        }

        return $attachments;
    }

    /**
     * What to call an attachment that could not be found.
     *
     * Read from the definition rather than from the client's answer, because the commonest
     * reason there is nothing to attach is that the client never answered at all — and a
     * failure naming `attribute:7` tells nobody which file was missing. The attribute may
     * itself have been deleted, which is the one case left with nothing better to say.
     *
     * Only ever reached on the way to a recorded failure, so the lookup costs nothing that
     * matters.
     */
    private function attachmentLabel(TemplateBinding $binding): string
    {
        $attribute = ClientAttribute::query()->find($binding->attributeId);

        if (! $attribute instanceof ClientAttribute) {
            return $binding->token();
        }

        $field = $binding->path === [] ? null : $attribute->findField($binding->path);

        return $field === null ? $attribute->name : $field->labelWithin($attribute->name);
    }

    /**
     * The client's answer to the attribute a binding names, if it still has one.
     */
    private function answerTo(TemplateBinding $binding, ?Client $client): ?ClientAttributeValue
    {
        return $client?->attributeValues
            ->first(fn (ClientAttributeValue $value): bool => $value->client_attribute_id === $binding->attributeId
                && $value->attribute->is_active);
    }

    /**
     * What a client's answer gives this binding, or null when it gives nothing.
     */
    private function answerFor(TemplateBinding $binding, ?Client $client): ?string
    {
        $answer = $this->answerTo($binding, $client);

        if (! $answer instanceof ClientAttributeValue) {
            return null;
        }

        $values = $answer->attribute->valuesAt($answer->value, $binding->path);

        if ($values === []) {
            return null;
        }

        // Each value is formatted as the node it came from: the attribute itself for a
        // whole answer, otherwise the field the path points at — which may be a repeater.
        $field = $binding->path === [] ? null : $answer->attribute->findField($binding->path);

        $formatted = array_map(
            fn (mixed $value): string => $field === null
                ? $answer->attribute->formatValue($value)
                : $field->format($value),
            $values,
        );

        $lines = array_filter($formatted, static fn (string $line): bool => $line !== '');

        return $lines === [] ? null : implode("\n", $lines);
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
