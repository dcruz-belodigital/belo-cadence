<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Data\Users\UserFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $filters = UserFilters::fromRequest($request);

        return view('admin.users.index', [
            'filters' => $filters,
            'users' => User::query()
                ->filtered($filters)
                ->with('roles')
                ->paginate(20)
                ->withQueryString(),
            'roles' => Role::query()->orderBy('name')->pluck('name', 'name'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $createUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = $createUser($request->toData());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', __('users.flash.created', ['name' => $user->name]));
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('roles');

        return view('admin.users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UpdateUserAction $updateUser,
    ): RedirectResponse {
        $this->authorize('update', $user);

        $updateUser($user, $request->toData());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', __('users.flash.updated', ['name' => $user->name]));
    }
}
