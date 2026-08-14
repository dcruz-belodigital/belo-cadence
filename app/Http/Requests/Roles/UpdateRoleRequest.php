<?php

declare(strict_types=1);

namespace App\Http\Requests\Roles;

use App\Data\Roles\RoleData;
use App\Enums\PermissionName;
use App\Models\Role;
use App\Support\EssentialAdministration;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->targetRole()->getKey()),
            ],
            'permissions' => ['array', $this->keepsAnAdministrator()],
            'permissions.*' => ['string', Rule::in(PermissionName::values())],
        ];
    }

    public function toData(): RoleData
    {
        /** @var list<string> $permissions */
        $permissions = $this->validated('permissions') ?? [];

        return new RoleData(
            name: $this->string('name')->toString(),
            permissionNames: array_values($permissions),
        );
    }

    /**
     * Refuses a permission change that would leave nobody able to manage users and roles.
     */
    private function keepsAnAdministrator(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $permissions = is_array($value) ? array_values(array_filter($value, 'is_string')) : [];

            $wouldLockEveryoneOut = $this->container
                ->make(EssentialAdministration::class)
                ->roleChangeWouldLeaveNoAdministrator($this->targetRole(), $permissions);

            if ($wouldLockEveryoneOut) {
                $fail(__('roles.errors.last_administrator'));
            }
        };
    }

    private function targetRole(): Role
    {
        $role = $this->route('role');

        assert($role instanceof Role);

        return $role;
    }
}
