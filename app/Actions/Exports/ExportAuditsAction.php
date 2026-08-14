<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Data\Audits\AuditFilters;
use App\Enums\ExportMode;
use App\Models\Audit;
use App\Support\Csv\CsvDocument;
use App\Support\ViewerTimezone;

final class ExportAuditsAction
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
    ) {}

    public function __invoke(AuditFilters $filters, ExportMode $mode): CsvDocument
    {
        return $mode === ExportMode::Raw
            ? $this->raw($filters)
            : $this->table($filters);
    }

    private function raw(AuditFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'audit-log-raw.csv',
            headers: [
                'id', 'created_at', 'user_id', 'action', 'auditable_type', 'auditable_id',
                'old_values', 'new_values', 'metadata',
            ],
            rows: Audit::query()
                ->filtered($filters)
                ->cursor()
                ->map(fn (Audit $audit): array => [
                    $audit->getKey(),
                    $audit->created_at->toIso8601String(),
                    $audit->user_id,
                    $audit->action->value,
                    $audit->auditable_type,
                    $audit->auditable_id,
                    $this->json($audit->old_values),
                    $this->json($audit->new_values),
                    $this->json($audit->metadata),
                ]),
        );
    }

    private function table(AuditFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'audit-log.csv',
            headers: [
                __('audit.columns.when'),
                __('audit.columns.actor'),
                __('audit.columns.action'),
                __('audit.columns.record'),
            ],
            rows: Audit::query()
                ->filtered($filters)
                ->with('user')
                ->cursor()
                ->map(fn (Audit $audit): array => [
                    $this->viewerTimezone->format($audit->created_at, (string) __('common.formats.datetime')),
                    $audit->user?->name ?? __('audit.system_actor'),
                    $audit->action->label(),
                    $this->record($audit),
                ]),
        );
    }

    private function record(Audit $audit): ?string
    {
        if ($audit->auditable_type === null) {
            return null;
        }

        return trim(sprintf('%s %s', $audit->auditableTypeLabel(), $audit->auditable_id === null ? '' : '#'.$audit->auditable_id));
    }

    /**
     * @param  array<string, mixed>|null  $values
     */
    private function json(?array $values): ?string
    {
        return $values === null
            ? null
            : json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
