<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Deactivates an account so it can no longer sign in.
 *
 * Nothing the person did is removed: their audit entries and notifications stay.
 */
final class DeactivateUserAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $user->update(['is_active' => false]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::UserDeactivated,
                auditable: $user,
                oldValues: ['is_active' => true],
                newValues: ['is_active' => false],
            ));

            return $user;
        });
    }
}
