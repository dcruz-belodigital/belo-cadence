<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Enums\ExportMode;
use App\Models\Role;
use App\Support\Csv\CsvDocument;
use Spatie\Permission\Models\Permission;

final class ExportRolesAction
{
    public const PERMISSION_SEPARATOR = ';';

    public function __invoke(ExportMode $mode): CsvDocument
    {
        return $mode === ExportMode::Raw
            ? $this->raw()
            : $this->table();
    }

    private function raw(): CsvDocument
    {
        return new CsvDocument(
            filename: 'roles-raw.csv',
            headers: ['id', 'name', 'permissions', 'users_count', 'created_at'],
            rows: Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->cursor()
                ->map(fn (Role $role): array => [
                    $role->getKey(),
                    $role->name,
                    $this->permissions($role),
                    (int) $role->users_count,
                    $role->created_at->toIso8601String(),
                ]),
        );
    }

    private function table(): CsvDocument
    {
        return new CsvDocument(
            filename: 'roles.csv',
            headers: [
                __('roles.columns.name'),
                __('roles.columns.permissions'),
                __('roles.columns.users'),
            ],
            rows: Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->cursor()
                ->map(fn (Role $role): array => [
                    $role->name,
                    $role->permissions->count(),
                    (int) $role->users_count,
                ]),
        );
    }

    private function permissions(Role $role): string
    {
        return $role->permissions
            ->map(fn (Permission $permission): string => $permission->name)
            ->sort()
            ->implode(self::PERMISSION_SEPARATOR);
    }
}
