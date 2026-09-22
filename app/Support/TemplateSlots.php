<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Notifications\EmailTemplateSlot;
use App\Enums\ClientAttributeType;
use App\Enums\EmailTemplate;
use App\Models\ClientAttribute;
use App\ValueObjects\TemplateAttachments;

/**
 * The blanks a template has, however it declares them.
 *
 * Most templates declare theirs in code, because their wording is source-controlled. The
 * blank template has no wording of its own, so its blanks are whatever `{{ placeholder }}`
 * tokens somebody typed into the message written on the schedule.
 *
 * The schedule form finds those tokens in the browser as they are typed, but this is the
 * authority: the server reads them again from what was actually submitted.
 */
final class TemplateSlots
{
    /**
     * Matches `{{ renewal_date }}`, allowing the spacing somebody is likely to type.
     */
    public const PLACEHOLDER = '/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/';

    /**
     * @return list<EmailTemplateSlot>
     */
    public static function for(EmailTemplate $template, ?string $message = null): array
    {
        return $template->hasOwnCopy()
            ? $template->slots()
            : self::inMessage($message ?? '');
    }

    /**
     * Everything the schedule and send forms need to draw a slot picker, prepared once.
     *
     * The template can be changed without a round trip, so the browser is handed the
     * slots of every template it could pick, and the choices each slot would offer. The
     * blank template is not in the map: its slots are whatever placeholders are being
     * typed at that moment, so the browser reads them from the message and fills them
     * from `any`.
     *
     * @param  iterable<ClientAttribute>  $attributes
     * @return array{by_template: array<string, list<array{key: string, label: string, choices: array<string, string>}>>, any: array<string, string>, attachments: array<string, string>}
     */
    public static function formOptions(iterable $attributes): array
    {
        $byTemplate = [];

        foreach (EmailTemplate::cases() as $template) {
            $slots = [];

            foreach ($template->slots() as $slot) {
                $slots[] = [
                    'key' => $slot->key,
                    'label' => $slot->label($template),
                    'choices' => $slot->choicesFrom($attributes),
                ];
            }

            $byTemplate[$template->value] = $slots;
        }

        return [
            'by_template' => $byTemplate,
            // A typed placeholder accepts anything printable, including a whole repeater.
            'any' => (new EmailTemplateSlot('any', [...ClientAttributeType::printableCases(), ClientAttributeType::Repeater], isMultiple: true))
                ->choicesFrom($attributes),
            // Attachments belong to no template, so they are offered once for all of them.
            'attachments' => self::attachmentSlot()->choicesFrom($attributes),
        ];
    }

    /**
     * Everything a notification could attach: every file a client attribute can hold.
     *
     * An attachment is expressed as a slot so it offers its choices the way a template
     * value does — one flat list of attribute-and-path tokens. It accepts a repeated path
     * because a file inside repeating rows means one attachment per row, which is the
     * whole reason a file may live in a row at all.
     */
    public static function attachmentSlot(): EmailTemplateSlot
    {
        return new EmailTemplateSlot('attachments', [ClientAttributeType::File], isMultiple: true);
    }

    /**
     * What a saved set of attachments is called, for a page that only reads them.
     *
     * Every attribute is offered here rather than only the active ones, so a schedule
     * still says what it attaches after that attribute has been retired — the send would
     * fail, and a page saying nothing would be a worse way to find that out.
     *
     * @param  iterable<ClientAttribute>  $attributes
     * @return list<string>
     */
    public static function attachmentLabels(TemplateAttachments $attachments, iterable $attributes): array
    {
        $choices = self::attachmentSlot()->choicesFrom($attributes);

        return array_map(
            static fn (string $token): string => $choices[$token] ?? $token,
            $attachments->tokens(),
        );
    }

    /**
     * The distinct placeholders in a typed message, in the order they first appear.
     *
     * @return list<EmailTemplateSlot>
     */
    public static function inMessage(string $message): array
    {
        preg_match_all(self::PLACEHOLDER, $message, $matches);

        $keys = array_values(array_unique($matches[1] ?? []));

        return array_map(
            static fn (string $key): EmailTemplateSlot => new EmailTemplateSlot(
                key: $key,
                isMultiple: true,
                isRequired: true,
                label: $key,
            ),
            $keys,
        );
    }

    /**
     * Puts the resolved values back into a typed message.
     *
     * The result is still plain text and is still escaped by the view, so nothing a
     * person types — in the message or in an answer — can introduce markup into an email.
     *
     * @param  array<string, string>  $values
     */
    public static function fill(string $message, array $values): string
    {
        return (string) preg_replace_callback(
            self::PLACEHOLDER,
            static fn (array $match): string => $values[$match[1]] ?? '',
            $message,
        );
    }
}
