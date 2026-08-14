<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Roles\CreateRoleAction;
use App\Actions\Roles\DeleteRoleAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => Role::query()
                ->withCount(['users', 'permissions'])
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', [
            'groupedPermissions' => PermissionName::grouped(),
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $createRole): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = $createRole($request->toData());

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', __('roles.flash.created', ['name' => $role->name]));
    }

    public function show(Role $role): View
    {
        $this->authorize('view', $role);

        $role->load(['permissions', 'users']);

        return view('admin.roles.show', [
            'role' => $role,
            'groupedPermissions' => PermissionName::grouped(),
        ]);
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'groupedPermissions' => PermissionName::grouped(),
        ]);
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role,
        UpdateRoleAction $updateRole,
    ): RedirectResponse {
        $this->authorize('update', $role);

        $updateRole($role, $request->toData());

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', __('roles.flash.updated', ['name' => $role->name]));
    }

    public function destroy(Role $role, DeleteRoleAction $deleteRole): RedirectResponse
    {
        // The policy refuses to delete a role that is still in use, or the last role
        // able to administer access.
        $this->authorize('delete', $role);

        $name = $role->name;

        $deleteRole($role);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('roles.flash.deleted', ['name' => $name]));
    }
}
