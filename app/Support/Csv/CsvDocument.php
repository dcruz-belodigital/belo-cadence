<?php

declare(strict_types=1);

namespace App\Support\Csv;

/**
 * A CSV file that has been described but not yet written.
 *
 * Rows are an iterable so exports can stream straight from a database cursor.
 */
final readonly class CsvDocument
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public function __construct(
        public string $filename,
        public array $headers,
        public iterable $rows,
    ) {}
}
