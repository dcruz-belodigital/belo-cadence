<?php

declare(strict_types=1);

namespace App\Support\Csv;

use App\Data\Imports\ColumnMapping;

/**
 * The shape of an importable CSV: which columns an import understands, which of them are
 * required, and an example row.
 *
 * The import, the downloadable template, the reference table and the column-matching step
 * all read this, so what a person downloads can never disagree with what the import
 * expects.
 */
final readonly class ImportTemplate
{
    /**
     * @param  list<ImportColumn>  $columns
     */
    public function __construct(
        public array $columns,
    ) {}

    /**
     * @return list<string>
     */
    public function columnNames(): array
    {
        return array_map(static fn (ImportColumn $column): string => $column->name, $this->columns);
    }

    /**
     * @return list<ImportColumn>
     */
    public function requiredColumns(): array
    {
        return array_values(array_filter($this->columns, static fn (ImportColumn $column): bool => $column->required));
    }

    /**
     * @return list<string>
     */
    public function requiredColumnNames(): array
    {
        return array_map(static fn (ImportColumn $column): string => $column->name, $this->requiredColumns());
    }

    /**
     * @return list<string>
     */
    public function exampleRow(): array
    {
        return array_map(static fn (ImportColumn $column): string => $column->example, $this->columns);
    }

    public function toDocument(string $filename): CsvDocument
    {
        return new CsvDocument(
            filename: $filename,
            headers: $this->columnNames(),
            rows: [$this->exampleRow()],
        );
    }

    /**
     * The matching this application would choose for a file's headings, so somebody only
     * has to correct it rather than fill it in.
     *
     * @param  list<string>  $headings
     */
    public function suggestMapping(array $headings): ColumnMapping
    {
        $mapping = [];

        foreach ($this->columns as $column) {
            $mapping[$column->name] = null;

            foreach ($headings as $heading) {
                if ($column->matches($heading) && ! in_array($heading, $mapping, true)) {
                    $mapping[$column->name] = $heading;

                    break;
                }
            }
        }

        return new ColumnMapping($mapping);
    }
}
