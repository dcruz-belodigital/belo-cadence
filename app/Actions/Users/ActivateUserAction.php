<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateUserAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $user->update(['is_active' => true]);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::UserActivated,
                auditable: $user,
                oldValues: ['is_active' => false],
                newValues: ['is_active' => true],
            ));

            return $user;
        });
    }
}
