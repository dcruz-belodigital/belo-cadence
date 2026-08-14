<?php

declare(strict_types=1);

namespace App\Support\Csv;

use App\Exceptions\InvalidCsvException;

/**
 * Reads a CSV file into rows keyed by their column name.
 *
 * The escape character is passed explicitly and left empty throughout: that is the
 * behaviour spreadsheet applications produce, and PHP deprecates relying on its own
 * non-standard default.
 */
final class CsvReader
{
    private const ESCAPE = '';

    public function read(string $path): CsvData
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw InvalidCsvException::unreadable();
        }

        try {
            $headers = fgetcsv($handle, escape: self::ESCAPE);

            if ($headers === false || $headers === [null]) {
                throw InvalidCsvException::missingHeaderRow();
            }

            $headers = $this->normalizeHeaders($headers);

            if ($headers === []) {
                throw InvalidCsvException::missingHeaderRow();
            }

            $rows = [];

            while (($values = fgetcsv($handle, escape: self::ESCAPE)) !== false) {
                if ($values === [null] || $this->isBlank($values)) {
                    continue;
                }

                $rows[] = $this->combine($headers, $values);
            }

            return new CsvData($headers, $rows);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string|null>  $headers
     * @return list<string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $index => $header) {
            $value = trim((string) $header);

            if ($index === 0) {
                $value = ltrim($value, "\xEF\xBB\xBF");
            }

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string|null>  $values
     * @return array<string, string>
     */
    private function combine(array $headers, array $values): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = trim((string) ($values[$index] ?? ''));
        }

        return $row;
    }

    /**
     * @param  list<string|null>  $values
     */
    private function isBlank(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
