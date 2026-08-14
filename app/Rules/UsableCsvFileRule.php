<?php

declare(strict_types=1);

namespace App\Rules;

use App\Exceptions\InvalidCsvException;
use App\Support\Csv\CsvReader;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Checks an uploaded file can actually be imported: it has a header row and at least one
 * row under it.
 *
 * This runs while the file is still in hand, so somebody is told immediately rather than
 * finding out at the matching step that there is nothing to match.
 */
final class UsableCsvFileRule implements ValidationRule
{
    public function __construct(
        private readonly CsvReader $csvReader,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('imports.errors.unreadable')->translate();

            return;
        }

        try {
            $csv = $this->csvReader->read((string) $value->getRealPath());
        } catch (InvalidCsvException $exception) {
            $fail($exception->getMessage());

            return;
        }

        if ($csv->rows === []) {
            $fail('imports.errors.no_rows')->translate();
        }
    }
}
