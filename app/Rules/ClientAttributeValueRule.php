<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\ClientAttributeType;
use App\Models\ClientAttribute;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

/**
 * Validates one client's answer to one attribute, whatever shape that answer has.
 *
 * The client form and the CSV import both validate through this object, so the two can
 * never disagree about what an acceptable value is — the same reasoning that keeps a
 * table and its export honest by sharing one `filtered()` scope.
 *
 * It is one rule on one key rather than a generated tree of keys, which matters because
 * every rule key needs somewhere on the page to appear. A repeater, however deeply it
 * nests, still reports under the attribute's own field, with the row it went wrong on
 * named in the message.
 *
 * Underneath it builds a nested validator, so the messages are Laravel's own and are
 * already translated into both catalogues.
 */
final class ClientAttributeValueRule implements ValidationRule
{
    /**
     * The most rows one repeater may hold, at any depth.
     */
    public const MAX_ROWS = 50;

    public function __construct(
        private readonly ClientAttribute $attribute,
    ) {}

    /**
     * The complete rule set for one attribute's answer.
     *
     * Both callers ask for it this way, so "required" can never mean one thing on the
     * form and another in the import. Whether an answer is present is left to Laravel's
     * own `required`, which already refuses a blank string and an empty repeater and
     * already has a translated message in both catalogues.
     *
     * @return list<mixed>
     */
    public static function for(ClientAttribute $attribute): array
    {
        return [$attribute->is_required ? 'required' : 'nullable', new self($attribute)];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->isBlank($value)) {
            return;
        }

        $validator = Validator::make(
            ['value' => $value],
            $this->rulesFor('value', $this->attribute->type, $this->attribute->options, $this->attribute->fields),
            [],
            $this->namesFor('value', $this->attribute->name, $this->attribute->type, $this->attribute->fields),
        );

        if ($validator->passes()) {
            return;
        }

        foreach ($validator->errors()->messages() as $key => $messages) {
            foreach ($messages as $message) {
                $fail($this->locate($key).$message);
            }
        }
    }

    /**
     * Whether an answer counts as no answer. An empty repeater is no answer too.
     */
    private function isBlank(mixed $value): bool
    {
        return $value === null
            || $value === []
            || (is_string($value) && trim($value) === '');
    }

    /**
     * The rules for a value at `$prefix`, recursing into a repeater's own fields.
     *
     * @param  list<ClientAttributeField>  $fields
     * @return array<string, list<mixed>>
     */
    private function rulesFor(
        string $prefix,
        ClientAttributeType $type,
        ClientAttributeChoices $choices,
        array $fields,
        bool $required = false,
    ): array {
        $leading = $required ? ['required'] : ['nullable'];

        if ($type->usesFields()) {
            $rules = [$prefix => [...$leading, 'array', 'max:'.self::MAX_ROWS]];

            foreach ($fields as $field) {
                $rules += $this->rulesFor(
                    $prefix.'.*.'.$field->key,
                    $field->type,
                    $field->choices,
                    $field->fields,
                    $field->isRequired,
                );
            }

            return $rules;
        }

        return [$prefix => [...$leading, ...$this->basicRules($type, $choices)]];
    }

    /**
     * @return list<mixed>
     */
    private function basicRules(ClientAttributeType $type, ClientAttributeChoices $choices): array
    {
        return match ($type) {
            ClientAttributeType::Text => ['string', 'max:255'],
            ClientAttributeType::LongText => ['string', 'max:5000'],
            ClientAttributeType::Number => ['numeric'],
            // Strict on purpose: "next tuesday" must not quietly become a date.
            ClientAttributeType::Date => ['string', 'date_format:Y-m-d'],
            ClientAttributeType::Boolean => ['boolean'],
            // Retired choices validate: a client already holding one stays editable.
            ClientAttributeType::Select => ['string', 'in:'.implode(',', $choices->values())],
            ClientAttributeType::Url => ['string', 'max:2048', 'url'],
            ClientAttributeType::Email => ['string', 'max:255', new EmailAddressRule],
            ClientAttributeType::Repeater => ['array'],
        };
    }

    /**
     * Human names for every key the nested validator can report on.
     *
     * Wildcard keys are understood: Laravel maps `value.0.name` back to `value.*.name`
     * when it looks a display name up.
     *
     * @param  list<ClientAttributeField>  $fields
     * @return array<string, string>
     */
    private function namesFor(string $prefix, string $name, ClientAttributeType $type, array $fields): array
    {
        $names = [$prefix => $name];

        if (! $type->usesFields()) {
            return $names;
        }

        foreach ($fields as $field) {
            $names += $this->namesFor($prefix.'.*.'.$field->key, $field->labelWithin($name), $field->type, $field->fields);
        }

        return $names;
    }

    /**
     * The row a message belongs to, read off the numeric segments of its key.
     *
     * Without this a repeater with eight rows reports "The Name field is required" eight
     * identical times and nobody can tell which row to fix.
     */
    private function locate(string $key): string
    {
        $rows = array_values(array_filter(
            explode('.', $key),
            static fn (string $segment): bool => ctype_digit($segment),
        ));

        if ($rows === []) {
            return '';
        }

        $locations = array_map(
            static fn (string $row): string => (string) __('client_attributes.errors.row', ['row' => (int) $row + 1]),
            $rows,
        );

        return implode(' ', $locations).' ';
    }
}
