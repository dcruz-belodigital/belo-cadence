<?php

declare(strict_types=1);

namespace App\Actions\Audits;

use App\Data\Audits\RecordAuditData;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Writes one audit entry.
 *
 * The actor is whoever is signed in. Work performed by the scheduler has no signed-in
 * user and is therefore recorded as a system action, never attributed to a person.
 */
final class RecordAuditAction
{
    public function __invoke(RecordAuditData $data): Audit
    {
        $actor = Auth::user();

        return Audit::query()->create([
            'user_id' => $actor instanceof User ? $actor->getKey() : null,
            'action' => $data->action,
            'auditable_type' => $data->auditable?->getMorphClass(),
            'auditable_id' => $data->auditable?->getKey(),
            'old_values' => $data->oldValues,
            'new_values' => $data->newValues,
            'metadata' => $data->metadata,
        ]);
    }
}
