<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Users\UpdateUserData;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $before = $this->auditValues($user);

            $user->fill([
                'name' => $data->name,
                'email' => $data->email,
            ]);

            if ($data->password !== null) {
                $user->password = $data->password;
            }

            $user->save();
            $user->syncRoles($data->roleNames);
            $user->load('roles');

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::UserUpdated,
                auditable: $user,
                oldValues: $before,
                newValues: $this->auditValues($user),
                metadata: $data->password === null ? null : ['password_changed' => true],
            ));

            return $user;
        });
    }

    /**
     * Passwords are never part of an audit entry, only the fact that one changed.
     *
     * @return array<string, mixed>
     */
    private function auditValues(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email->value,
            'roles' => $user->roles->pluck('name')->all(),
        ];
    }
}
