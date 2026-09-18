<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Data\Clients\ClientFilters;
use App\Enums\ExportMode;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeValue;
use App\Support\Csv\CsvDocument;
use App\Support\ViewerTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Builds the clients CSV.
 *
 * Both modes honour the current search, filters and sorting; they differ in what the
 * columns mean. Raw is for machines: every stored column, raw enum values and ISO
 * timestamps. Table mirrors the on-screen table for a person to read.
 *
 * Active custom attributes are appended as further columns, in their chosen order, so
 * the columns that were always there stay where they were and anything already reading
 * this file keeps working. Only the raw file round-trips back through the import.
 */
final class ExportClientsAction
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
    ) {}

    public function __invoke(ClientFilters $filters, ExportMode $mode): CsvDocument
    {
        // Resolved once and shared by the header and every row: the file streams, so a
        // second lookup could disagree with the header already written.
        $attributes = ClientAttribute::query()->active()->get();

        return $mode === ExportMode::Raw
            ? $this->raw($filters, $attributes)
            : $this->table($filters, $attributes);
    }

    /**
     * @param  Collection<int, ClientAttribute>  $attributes
     */
    private function raw(ClientFilters $filters, Collection $attributes): CsvDocument
    {
        return new CsvDocument(
            filename: 'clients-raw.csv',
            headers: [
                'id', 'name', 'email', 'status', 'notes', 'created_at', 'updated_at', 'deleted_at',
                ...$attributes->map(fn (ClientAttribute $attribute): string => 'attribute_'.$attribute->key)->all(),
            ],
            rows: $this->clients($filters)->map(fn (Client $client): array => [
                $client->getKey(),
                $client->name,
                $client->email->value,
                $client->status->value,
                $client->notes,
                $client->created_at->toIso8601String(),
                $client->updated_at->toIso8601String(),
                $client->deleted_at?->toIso8601String(),
                ...$this->answers($client, $attributes, machineReadable: true),
            ]),
        );
    }

    /**
     * @param  Collection<int, ClientAttribute>  $attributes
     */
    private function table(ClientFilters $filters, Collection $attributes): CsvDocument
    {
        return new CsvDocument(
            filename: 'clients.csv',
            headers: [
                __('clients.columns.name'),
                __('clients.columns.email'),
                __('clients.columns.status'),
                __('clients.columns.schedules'),
                __('clients.columns.created'),
                ...$attributes->map(fn (ClientAttribute $attribute): string => $attribute->name)->all(),
            ],
            rows: $this->clients($filters)
                ->map(fn (Client $client): array => [
                    $client->name,
                    $client->email->value,
                    $client->status->label(),
                    (int) $client->notification_schedules_count,
                    $this->formatDate($client->created_at),
                    ...$this->answers($client, $attributes, machineReadable: false),
                ]),
        );
    }

    /**
     * The clients to write, streamed.
     *
     * `lazy()` rather than `cursor()`: a cursor hydrates one model at a time and never
     * eager loads, so reading the answers off it would issue a query per client — and
     * because those models never pass through `hydrate()` they are not marked against
     * lazy loading either, so nothing would report it. `Client::filtered()` ends with a
     * unique key so paging by offset cannot repeat or skip a row.
     *
     * @return LazyCollection<int, Client>
     */
    private function clients(ClientFilters $filters): LazyCollection
    {
        return Client::query()
            ->filtered($filters)
            ->withCount('notificationSchedules')
            ->with('attributeValues')
            ->lazy(500);
    }

    /**
     * One cell per active attribute, in the same order as the header.
     *
     * Read from the definitions rather than from the client's own rows, which are in
     * whatever order the database returned and may be missing entirely.
     *
     * @param  Collection<int, ClientAttribute>  $attributes
     * @return list<string>
     */
    private function answers(Client $client, Collection $attributes, bool $machineReadable): array
    {
        $answers = $client->attributeValues
            ->mapWithKeys(fn (ClientAttributeValue $value): array => [$value->client_attribute_id => $value->value]);

        return $attributes->map(function (ClientAttribute $attribute) use ($answers, $machineReadable): string {
            $value = $answers->get($attribute->getKey());

            return $machineReadable ? $attribute->toCsv($value) : $attribute->formatValue($value);
        })->all();
    }

    private function formatDate(CarbonImmutable $value): string
    {
        return $this->viewerTimezone->format($value, (string) __('common.formats.datetime'));
    }
}
