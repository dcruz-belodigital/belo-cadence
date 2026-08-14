<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Roles\RoleData;
use App\Enums\AuditAction;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

final class CreateRoleAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(RoleData $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::create(['name' => $data->name, 'guard_name' => 'web']);

            $role->syncPermissions($data->permissionNames);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::RoleCreated,
                auditable: $role,
                newValues: [
                    'name' => $role->name,
                    'permissions' => $data->permissionNames,
                ],
            ));

            return $role;
        });
    }
}
