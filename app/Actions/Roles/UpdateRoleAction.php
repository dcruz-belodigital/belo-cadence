<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Roles\RoleData;
use App\Enums\AuditAction;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

final class UpdateRoleAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(Role $role, RoleData $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $before = [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
            ];

            $role->update(['name' => $data->name]);
            $role->syncPermissions($data->permissionNames);
            $role->load('permissions');

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::RoleUpdated,
                auditable: $role,
                oldValues: $before,
                newValues: [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                ],
            ));

            return $role;
        });
    }
}
