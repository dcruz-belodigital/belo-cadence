<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PermissionName;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;

/**
 * Guards the application against being left without anybody able to administer access.
 *
 * Permissions in Belo Cadence are always granted through roles, so a user's effective
 * permissions are the union of their roles' permissions. Each method answers a
 * "what if" question about a change that is about to be made.
 */
final class EssentialAdministration
{
    public function deactivationWouldLeaveNoAdministrator(User $user): bool
    {
        return ! $this->administratorExists(
            fn (User $candidate): Collection => $candidate->is($user)
                ? new Collection
                : $this->permissionsOf($candidate)
        );
    }

    /**
     * @param  list<string>  $roleNames
     */
    public function userRoleChangeWouldLeaveNoAdministrator(User $user, array $roleNames): bool
    {
        $replacement = $this->permissionsOfRoleNames($roleNames);

        return ! $this->administratorExists(
            fn (User $candidate): Collection => $candidate->is($user)
                ? $replacement
                : $this->permissionsOf($candidate)
        );
    }

    /**
     * @param  list<string>  $permissionNames
     */
    public function roleChangeWouldLeaveNoAdministrator(Role $role, array $permissionNames): bool
    {
        return ! $this->administratorExists(
            fn (User $candidate): Collection => $this->permissionsOf($candidate, $role, $permissionNames)
        );
    }

    public function roleDeletionWouldLeaveNoAdministrator(Role $role): bool
    {
        return $this->roleChangeWouldLeaveNoAdministrator($role, []);
    }

    /**
     * Whether the given permission names are enough to manage users and roles again.
     *
     * @param  Collection<int, string>  $permissionNames
     */
    public function grantsAdministration(Collection $permissionNames): bool
    {
        return collect(PermissionName::essentialAdministrationPermissions())
            ->every(fn (PermissionName $permission): bool => $permissionNames->contains($permission->value));
    }

    /**
     * @param  Closure(User): Collection<int, string>  $permissionResolver
     */
    private function administratorExists(Closure $permissionResolver): bool
    {
        // The user base of an internal application is small enough to evaluate in
        // memory, which keeps the "what if" simulations readable.
        return User::query()
            ->active()
            ->with('roles.permissions')
            ->get()
            ->contains(fn (User $candidate): bool => $this->grantsAdministration($permissionResolver($candidate)));
    }

    /**
     * The user's effective permissions, optionally with one role's permissions replaced.
     *
     * @param  list<string>|null  $replacementPermissionNames
     * @return Collection<int, string>
     */
    private function permissionsOf(User $user, ?Role $role = null, ?array $replacementPermissionNames = null): Collection
    {
        return $user->roles
            ->flatMap(function (Role $userRole) use ($role, $replacementPermissionNames): array {
                if ($role !== null && $userRole->is($role)) {
                    return $replacementPermissionNames ?? [];
                }

                return $userRole->permissions->pluck('name')->all();
            })
            ->unique()
            ->values();
    }

    /**
     * @param  list<string>  $roleNames
     * @return Collection<int, string>
     */
    private function permissionsOfRoleNames(array $roleNames): Collection
    {
        if ($roleNames === []) {
            return new Collection;
        }

        return Role::query()
            ->with('permissions')
            ->whereIn('name', $roleNames)
            ->get()
            ->flatMap(fn (Role $role): array => $role->permissions->pluck('name')->all())
            ->unique()
            ->values();
    }
}
