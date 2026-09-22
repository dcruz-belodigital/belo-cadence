<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\ClientAttributeFile;
use App\ValueObjects\StoredFile;
use Carbon\CarbonImmutable;

/**
 * The kinds of field a client attribute can be.
 *
 * Every arm below is written without a `default`, so adding a case makes PHP throw an
 * `UnhandledMatchError` at the first method that has not been taught about it. That
 * exhaustiveness is what lets the rest of the application — the form, the rule, the
 * export, the import and the email slots — trust this enum rather than guard against it.
 *
 * A repeater is the one composite: it holds a list of rows whose cells are themselves
 * fields, and one of those may be another repeater.
 *
 * A file is the one type whose answer is not typed. It is uploaded, its bytes live on a
 * disk, and the answer is a reference to the {@see ClientAttributeFile} row that owns
 * them. Everything reading such an answer goes through {@see StoredFile} rather than
 * casting the value to a string.
 */
enum ClientAttributeType: string
{
    case Text = 'text';
    case LongText = 'long_text';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';
    case Select = 'select';
    case Url = 'url';
    case Email = 'email';
    case Repeater = 'repeater';
    case File = 'file';

    /**
     * Everything a template slot can print: one value, and one that reads as text.
     *
     * @return list<self>
     */
    public static function printableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $type): bool => $type->isBasic() && $type->isPrintable(),
        ));
    }

    public function label(): string
    {
        return __('enums.client_attribute_type.'.$this->value);
    }

    /**
     * Whether this type holds one value rather than a list of rows.
     */
    public function isBasic(): bool
    {
        return $this !== self::Repeater;
    }

    /**
     * Whether a value of this type can be written into an email's wording.
     *
     * A file cannot. It is attached rather than printed, and putting its name where a
     * person expected a value would read as a mistake — so a file fills an attachment
     * and nothing else.
     */
    public function isPrintable(): bool
    {
        return $this !== self::File;
    }

    /**
     * Which partial renders the field on the client form.
     */
    public function partial(): string
    {
        return match ($this) {
            self::Text, self::Number, self::Date, self::Url, self::Email => 'input',
            self::LongText => 'textarea',
            self::Boolean => 'checkbox',
            self::Select => 'select',
            self::Repeater => 'repeater',
            self::File => 'file',
        };
    }

    /**
     * The `type` attribute of the input, for the five types that share one.
     *
     * A date is a calendar date and never an instant: it is stored and submitted as
     * `Y-m-d` and is deliberately not run through `ConvertsViewerDateTimes`, because
     * converting a bare date to UTC from a negative offset moves it to the day before.
     */
    public function inputType(): string
    {
        return match ($this) {
            self::Text, self::LongText, self::Boolean, self::Select, self::Repeater => 'text',
            self::Number => 'number',
            self::Date => 'date',
            self::Url => 'url',
            self::Email => 'email',
            self::File => 'file',
        };
    }

    public function usesChoices(): bool
    {
        return $this === self::Select;
    }

    public function usesFields(): bool
    {
        return $this === self::Repeater;
    }

    /**
     * Whether an answer of this type is bytes on a disk rather than something typed.
     */
    public function usesFile(): bool
    {
        return $this === self::File;
    }

    /**
     * Normalises a submitted or decoded single value into the shape that is stored.
     *
     * Blank always becomes null, so "unset" has one representation everywhere.
     */
    public function normalise(mixed $value): mixed
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return match ($this) {
            self::Text, self::LongText, self::Select, self::Url, self::Email, self::Date => trim((string) $value),
            self::Number => $this->normaliseNumber($value),
            self::Boolean => $this->normaliseBoolean($value),
            self::Repeater => is_array($value) ? array_values($value) : null,
            /*
            | A file is uploaded, never typed, so there is nothing here to normalise: the
            | client form reads the upload out of the request itself (see
            | `ValidatesClientAttributes`). Answering null is also what keeps a file out
            | of the CSV import, which has no bytes to offer.
            */
            self::File => null,
        };
    }

    /**
     * The value as it is written to, and read back from, the machine-readable CSV.
     *
     * A repeater is not handled here: its cell is JSON, which the attribute builds
     * because it needs the sub-field tree.
     */
    public function toCsv(mixed $value): string
    {
        return match ($this) {
            self::Text, self::LongText, self::Select, self::Url, self::Email, self::Date => (string) $value,
            self::Number => $this->numberToString($value),
            self::Boolean => $value === true ? '1' : '0',
            // Its name is all a spreadsheet can say about a file; the bytes stay here.
            self::File => StoredFile::fromValue($value)?->name ?? '',
            self::Repeater => '',
        };
    }

    /**
     * One value as a person reads it.
     *
     * A repeater is not handled here: a list of rows needs the fields it is made of, so
     * `ClientAttributeField::formatRows()` does that.
     */
    public function format(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($this === self::Boolean) {
            return (string) ($value === true ? __('common.yes') : __('common.no'));
        }

        // The guard is what makes the parse safe; a stored date is always exactly this shape.
        if ($this === self::Date && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value)->format((string) __('common.formats.date'));
        }

        return $this->toCsv($value);
    }

    private function normaliseNumber(mixed $value): int|float|string|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $candidate = trim((string) $value);

        // A value the rule will reject is handed back untouched so it can be reported.
        if (! is_numeric($candidate)) {
            return $candidate;
        }

        return str_contains($candidate, '.') ? (float) $candidate : (int) $candidate;
    }

    private function normaliseBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * A plain decimal with a dot separator and no thousands separator, so a spreadsheet
     * cannot reinterpret it on the way back in.
     */
    private function numberToString(mixed $value): string
    {
        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
        }

        return (string) $value;
    }
}
