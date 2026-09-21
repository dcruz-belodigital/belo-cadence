<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\ClientAttributeType;
use InvalidArgumentException;

/**
 * One cell of a repeater row.
 *
 * A field is the same shape as the attribute that contains it, which is what makes the
 * whole feature recursive for free: a field may itself be a repeater holding its own
 * fields, and every consumer — validation, the form, the detail page, the CSV and the
 * email slots — walks the tree with the same code at every level.
 *
 * A field does not have to be named. An unnamed one is a bare value: the form draws its
 * input with nothing above it and a recorded row reads as the value alone, which is what
 * turns a repeater of one field into a plain list. It still has a key, because a key is
 * what an answer is filed under — the definition form numbers one when nobody types a
 * name to derive it from.
 *
 * Depth is capped by `ClientAttribute::MAX_DEPTH`, which the definition form enforces.
 */
final readonly class ClientAttributeField
{
    /**
     * @param  list<self>  $fields
     */
    public function __construct(
        public string $key,
        public string $name,
        public ClientAttributeType $type,
        public bool $isRequired = false,
        public ClientAttributeChoices $choices = new ClientAttributeChoices,
        public array $fields = [],
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException('A repeater field needs a key.');
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): ?self
    {
        $key = is_string($raw['key'] ?? null) ? trim($raw['key']) : '';
        $name = is_string($raw['name'] ?? null) ? trim($raw['name']) : '';
        $type = ClientAttributeType::tryFrom(is_string($raw['type'] ?? null) ? $raw['type'] : '');

        if ($key === '' || ! $type instanceof ClientAttributeType) {
            return null;
        }

        return new self(
            key: $key,
            name: $name,
            type: $type,
            isRequired: (bool) ($raw['is_required'] ?? false),
            choices: ClientAttributeChoices::fromArray(is_array($raw['choices'] ?? null) ? $raw['choices'] : []),
            fields: self::listFromArray(is_array($raw['fields'] ?? null) ? $raw['fields'] : []),
        );
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return list<self>
     */
    public static function listFromArray(array $raw): array
    {
        $fields = [];

        foreach ($raw as $entry) {
            $field = is_array($entry) ? self::fromArray($entry) : null;

            if ($field instanceof self) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param  list<self>  $fields
     * @return list<array<string, mixed>>
     */
    public static function listToArray(array $fields): array
    {
        return array_map(static fn (self $field): array => $field->toArray(), $fields);
    }

    /**
     * An empty row for these fields, which is what the form hands to the browser so a
     * newly added row already has every cell it needs — at every depth.
     *
     * @param  list<self>  $fields
     * @return array<string, mixed>
     */
    public static function blankRow(array $fields): array
    {
        $row = [];

        foreach ($fields as $field) {
            $row[$field->key] = $field->type->usesFields() ? [] : '';
        }

        return $row;
    }

    /**
     * A value of this field as a person reads it, rows and all.
     */
    public function format(mixed $value): string
    {
        return $this->type->usesFields()
            ? self::formatRows(is_array($value) ? $value : [], $this->fields)
            : $this->type->format($value);
    }

    /**
     * A repeater's rows, one line each, with a nested repeater folded into its line.
     *
     * @param  array<int, mixed>  $rows
     * @param  list<self>  $fields
     */
    public static function formatRows(array $rows, array $fields): string
    {
        $lines = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = [];

            foreach ($fields as $field) {
                $cell = $row[$field->key] ?? null;

                if ($cell === null || $cell === '' || $cell === []) {
                    continue;
                }

                $formatted = $field->type->usesFields()
                    ? '('.str_replace("\n", ' / ', $field->format($cell)).')'
                    : $field->format($cell);

                // A field nobody named is read as its value alone, not as "name: value".
                $cells[] = $field->isNamed() ? $field->name.': '.$formatted : $formatted;
            }

            if ($cells !== []) {
                $lines[] = implode(', ', $cells);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Whether this field names itself.
     */
    public function isNamed(): bool
    {
        return $this->name !== '';
    }

    /**
     * What to call this field where something has to be called something — a validation
     * message, an email template slot. An unnamed field borrows the name of whatever
     * contains it, because it has none of its own to point at.
     */
    public function labelWithin(string $container): string
    {
        return $this->isNamed() ? $this->name : $container;
    }

    public function find(string $key): ?self
    {
        foreach ($this->fields as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $field = [
            'key' => $this->key,
            'name' => $this->name,
            'type' => $this->type->value,
            'is_required' => $this->isRequired,
        ];

        if ($this->type->usesChoices()) {
            $field['choices'] = $this->choices->toArray();
        }

        if ($this->type->usesFields()) {
            $field['fields'] = self::listToArray($this->fields);
        }

        return $field;
    }
}
