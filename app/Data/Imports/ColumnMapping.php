<?php

declare(strict_types=1);

namespace App\Data\Imports;

/**
 * Which heading in somebody's file provides each column an import understands.
 *
 * A column with no heading behind it is simply absent, which is how an optional column
 * that nobody supplied is represented.
 */
final readonly class ColumnMapping
{
    /**
     * @param  array<string, string|null>  $headings  Column name to file heading.
     */
    public function __construct(
        public array $headings,
    ) {}

    public function headingFor(string $column): ?string
    {
        return $this->headings[$column] ?? null;
    }

    public function isMapped(string $column): bool
    {
        return $this->headingFor($column) !== null;
    }

    /**
     * The value of a column for one row of the file.
     *
     * @param  array<string, string>  $row
     */
    public function value(array $row, string $column): string
    {
        $heading = $this->headingFor($column);

        return $heading === null ? '' : trim($row[$heading] ?? '');
    }

    /**
     * @return list<string>
     */
    public function mappedColumns(): array
    {
        return array_values(array_keys(array_filter($this->headings, static fn (?string $heading): bool => $heading !== null)));
    }
}
