<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Removes a role. Whether removing it is safe is decided by the role policy.
 */
final class DeleteRoleAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $name = $role->name;
            $permissions = $role->permissions->pluck('name')->sort()->values()->all();

            $role->delete();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::RoleDeleted,
                oldValues: [
                    'name' => $name,
                    'permissions' => $permissions,
                ],
            ));
        });
    }
}
