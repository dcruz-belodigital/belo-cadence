<?php

declare(strict_types=1);

namespace App\Http\Requests\Imports;

use App\Data\Imports\ColumnMapping;
use App\Support\Csv\CsvReader;
use App\Support\Csv\ImportTemplate;
use App\Support\Csv\PendingImportStore;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The column matching somebody confirmed for an uploaded file.
 *
 * Only headings that really exist in their file are accepted, every required column has
 * to be matched, and one heading cannot be used twice — otherwise a column would silently
 * import the wrong values.
 */
abstract class MapImportRequest extends FormRequest
{
    /**
     * The resource being imported, which is how the pending file is found.
     */
    abstract protected function resource(): string;

    abstract protected function template(): ImportTemplate;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mapping' => [
                'array',
                $this->fileIsStillWaiting(),
                $this->coversEveryRequiredColumn(),
                $this->usesEachHeadingOnce(),
            ],
            'mapping.*' => ['nullable', 'string', Rule::in($this->headings())],
        ];
    }

    public function toMapping(): ColumnMapping
    {
        /** @var array<string, string|null> $submitted */
        $submitted = $this->validated('mapping') ?? [];

        $mapping = [];

        foreach ($this->template()->columnNames() as $column) {
            $heading = $submitted[$column] ?? null;

            $mapping[$column] = is_string($heading) && $heading !== '' ? $heading : null;
        }

        return new ColumnMapping($mapping);
    }

    public function csvPath(): string
    {
        $store = $this->container->make(PendingImportStore::class);
        $pending = $store->get($this->resource());

        return $pending === null ? '' : $store->absolutePath($pending);
    }

    /**
     * The headings of the file that is waiting to be imported.
     *
     * @return list<string>
     */
    protected function headings(): array
    {
        $path = $this->csvPath();

        if ($path === '') {
            return [];
        }

        return $this->container->make(CsvReader::class)->read($path)->headers;
    }

    private function fileIsStillWaiting(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->csvPath() === '') {
                $fail(__('imports.errors.expired'));
            }
        };
    }

    private function coversEveryRequiredColumn(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $submitted = is_array($value) ? $value : [];

            foreach ($this->template()->requiredColumns() as $column) {
                $heading = $submitted[$column->name] ?? null;

                if (! is_string($heading) || $heading === '') {
                    $fail(__('imports.errors.unmapped_required', ['column' => $column->label]));
                }
            }
        };
    }

    private function usesEachHeadingOnce(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $used = array_filter(is_array($value) ? $value : [], static fn (mixed $heading): bool => is_string($heading) && $heading !== '');

            foreach (array_count_values($used) as $heading => $count) {
                if ($count > 1) {
                    $fail(__('imports.errors.duplicate_heading', ['heading' => $heading]));
                }
            }
        };
    }
}
