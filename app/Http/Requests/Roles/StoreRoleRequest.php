<?php

declare(strict_types=1);

namespace App\Http\Requests\Roles;

use App\Data\Roles\RoleData;
use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            // Permissions are source code: only names the application declares are accepted.
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
}
