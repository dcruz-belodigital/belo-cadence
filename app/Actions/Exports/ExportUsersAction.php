<?php

declare(strict_types=1);

namespace App\Actions\Exports;

use App\Data\Users\UserFilters;
use App\Enums\ExportMode;
use App\Models\Role;
use App\Models\User;
use App\Support\Csv\CsvDocument;
use App\Support\Csv\ImportTemplates;
use App\Support\ViewerTimezone;
use Carbon\CarbonImmutable;

/**
 * Builds the users CSV. Password hashes are never part of an export.
 */
final class ExportUsersAction
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
    ) {}

    public function __invoke(UserFilters $filters, ExportMode $mode): CsvDocument
    {
        return $mode === ExportMode::Raw
            ? $this->raw($filters)
            : $this->table($filters);
    }

    private function raw(UserFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'users-raw.csv',
            headers: ['id', 'name', 'email', 'is_active', 'roles', 'color_scheme', 'locale', 'timezone', 'created_at', 'updated_at'],
            rows: User::query()
                ->filtered($filters)
                ->with('roles')
                ->cursor()
                ->map(fn (User $user): array => [
                    $user->getKey(),
                    $user->name,
                    $user->email->value,
                    $user->is_active ? '1' : '0',
                    $this->roles($user),
                    $user->color_scheme->value,
                    $user->locale->value,
                    $user->timezone->value,
                    $user->created_at->toIso8601String(),
                    $user->updated_at->toIso8601String(),
                ]),
        );
    }

    private function table(UserFilters $filters): CsvDocument
    {
        return new CsvDocument(
            filename: 'users.csv',
            headers: [
                __('users.columns.name'),
                __('users.columns.email'),
                __('users.columns.roles'),
                __('users.columns.state'),
                __('users.columns.created'),
            ],
            rows: User::query()
                ->filtered($filters)
                ->with('roles')
                ->cursor()
                ->map(fn (User $user): array => [
                    $user->name,
                    $user->email->value,
                    $this->roles($user),
                    $user->is_active ? __('users.states.active') : __('users.states.inactive'),
                    $this->formatDate($user->created_at),
                ]),
        );
    }

    private function roles(User $user): string
    {
        return $user->roles->map(fn (Role $role): string => $role->name)->implode(ImportTemplates::ROLE_SEPARATOR);
    }

    private function formatDate(CarbonImmutable $value): string
    {
        return $this->viewerTimezone->format($value, (string) __('common.formats.datetime'));
    }
}
