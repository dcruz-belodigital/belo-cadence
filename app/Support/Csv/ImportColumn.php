<?php

declare(strict_types=1);

namespace App\Support\Csv;

use Illuminate\Support\Str;

/**
 * One column an import understands: the name it has in a file, how it is described to a
 * person, whether it must be present, and an example value.
 */
final readonly class ImportColumn
{
    public function __construct(
        public string $name,
        public string $label,
        public bool $required,
        public string $example,
    ) {}

    /**
     * Whether a column heading from somebody's file means this column.
     *
     * Both sides are reduced to plain words, so "Email address", "email_address" and
     * "EMAIL ADDRESS" all find the same column.
     */
    public function matches(string $heading): bool
    {
        $candidate = self::normalise($heading);

        return $candidate !== ''
            && in_array($candidate, [self::normalise($this->name), self::normalise($this->label)], true);
    }

    private static function normalise(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
    }
}
