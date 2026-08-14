<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Updates the signed-in person's own name. Roles and activation stay administrative.
 */
final class UpdateProfileAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(User $user, string $name): User
    {
        return DB::transaction(function () use ($user, $name): User {
            $before = ['name' => $user->name];

            $user->update(['name' => $name]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::UserUpdated,
                auditable: $user,
                oldValues: $before,
                newValues: ['name' => $user->name],
            ));

            return $user;
        });
    }
}
