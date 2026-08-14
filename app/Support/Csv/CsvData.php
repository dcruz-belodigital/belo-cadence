<?php

declare(strict_types=1);

namespace App\Support\Csv;

/**
 * A parsed CSV file: its header row, and every data row keyed by column name.
 */
final readonly class CsvData
{
    /**
     * @param  list<string>  $headers
     * @param  list<array<string, string>>  $rows
     */
    public function __construct(
        public array $headers,
        public array $rows,
    ) {}

    /**
     * @param  list<string>  $expected
     * @return list<string>
     */
    public function missingHeaders(array $expected): array
    {
        return array_values(array_diff($expected, $this->headers));
    }

    /**
     * @param  list<string>  $known
     * @return list<string>
     */
    public function unexpectedHeaders(array $known): array
    {
        return array_values(array_diff($this->headers, $known));
    }
}
