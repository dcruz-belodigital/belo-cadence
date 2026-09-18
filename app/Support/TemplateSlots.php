<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Notifications\EmailTemplateSlot;
use App\Enums\ClientAttributeType;
use App\Enums\EmailTemplate;
use App\Models\ClientAttribute;

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
     * @return array{by_template: array<string, list<array{key: string, label: string, choices: array<string, string>}>>, any: array<string, string>}
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
            // A typed placeholder accepts anything, including a whole repeater.
            'any' => (new EmailTemplateSlot('any', [...ClientAttributeType::basicCases(), ClientAttributeType::Repeater], isMultiple: true))
                ->choicesFrom($attributes),
        ];
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
