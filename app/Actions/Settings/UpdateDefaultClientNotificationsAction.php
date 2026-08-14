<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Settings\DefaultClientNotificationData;
use App\Data\Settings\UpdateDefaultClientNotificationsData;
use App\Enums\AuditAction;
use App\Models\DefaultClientNotification;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the set of notifications offered when a client is created.
 *
 * Schedules that were created from an earlier version of this list are untouched:
 * applying a default copies it, so this configuration only ever affects future setup.
 */
final class UpdateDefaultClientNotificationsAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(UpdateDefaultClientNotificationsData $data): void
    {
        DB::transaction(function () use ($data): void {
            $before = $this->currentEntries();

            $keptIds = [];

            foreach ($data->entries as $entry) {
                $keptIds[] = DefaultClientNotification::query()->updateOrCreate(
                    [
                        'template' => $entry->template,
                        'frequency' => $entry->frequency,
                    ],
                    ['is_enabled_by_default' => $entry->isEnabledByDefault],
                )->getKey();
            }

            DefaultClientNotification::query()->whereNotIn('id', $keptIds)->delete();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::DefaultClientNotificationsUpdated,
                oldValues: ['entries' => $before],
                newValues: ['entries' => $this->describe($data->entries)],
            ));
        });
    }

    /**
     * @return list<array<string, string|bool>>
     */
    private function currentEntries(): array
    {
        return DefaultClientNotification::query()
            ->orderBy('id')
            ->get()
            ->map(fn (DefaultClientNotification $default): array => [
                'template' => $default->template->value,
                'frequency' => $default->frequency->value,
                'is_enabled_by_default' => $default->is_enabled_by_default,
            ])
            ->all();
    }

    /**
     * @param  list<DefaultClientNotificationData>  $entries
     * @return list<array<string, string|bool>>
     */
    private function describe(array $entries): array
    {
        return array_map(static fn (DefaultClientNotificationData $entry): array => [
            'template' => $entry->template->value,
            'frequency' => $entry->frequency->value,
            'is_enabled_by_default' => $entry->isEnabledByDefault,
        ], $entries);
    }
}
