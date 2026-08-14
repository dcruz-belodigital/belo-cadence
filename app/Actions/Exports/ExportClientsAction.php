<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Data\Clients\ClientFilters;
use App\Enums\ExportMode;
use App\Models\Client;
use App\Support\Csv\CsvDocument;
use App\Support\ViewerTimezone;
use Carbon\CarbonImmutable;

/**
 * Builds the clients CSV.
 *
 * Both modes honour the current search, filters and sorting; they differ in what the
 * columns mean. Raw is for machines: every stored column, raw enum values and ISO
 * timestamps. Table mirrors the on-screen table for a person to read.
 */
final class ExportClientsAction
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
    ) {}

    public function __invoke(ClientFilters $filters, ExportMode $mode): CsvDocument
    {
        return $mode === ExportMode::Raw
            ? $this->raw($filters)
            : $this->table($filters);
    }

    private function raw(ClientFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'clients-raw.csv',
            headers: ['id', 'name', 'email', 'status', 'notes', 'created_at', 'updated_at', 'deleted_at'],
            rows: Client::query()
                ->filtered($filters)
                ->cursor()
                ->map(fn (Client $client): array => [
                    $client->getKey(),
                    $client->name,
                    $client->email->value,
                    $client->status->value,
                    $client->notes,
                    $client->created_at->toIso8601String(),
                    $client->updated_at->toIso8601String(),
                    $client->deleted_at?->toIso8601String(),
                ]),
        );
    }

    private function table(ClientFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'clients.csv',
            headers: [
                __('clients.columns.name'),
                __('clients.columns.email'),
                __('clients.columns.status'),
                __('clients.columns.schedules'),
                __('clients.columns.created'),
            ],
            rows: Client::query()
                ->filtered($filters)
                ->withCount('notificationSchedules')
                ->cursor()
                ->map(fn (Client $client): array => [
                    $client->name,
                    $client->email->value,
                    $client->status->label(),
                    (int) $client->notification_schedules_count,
                    $this->formatDate($client->created_at),
                ]),
        );
    }

    private function formatDate(CarbonImmutable $value): string
    {
        return $this->viewerTimezone->format($value, (string) __('common.formats.datetime'));
    }
}
