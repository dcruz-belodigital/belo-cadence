<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Replaces a person's own password.
 *
 * Only the fact that the password changed is recorded, never the value.
 */
final class UpdateUserPasswordAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(User $user, string $password): User
    {
        return DB::transaction(function () use ($user, $password): User {
            $user->update(['password' => $password]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::UserUpdated,
                auditable: $user,
                metadata: ['password_changed' => true],
            ));

            return $user;
        });
    }
}
