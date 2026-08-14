<?php

declare(strict_types=1);

namespace App\Support\Csv;

use App\Enums\ClientStatus;

/**
 * The importable CSV shapes this application understands.
 *
 * A pure catalogue: adding a column means changing it here, and the import, the reference
 * table, the column-matching step and the downloadable template all follow.
 */
final class ImportTemplates
{
    /**
     * Roles are listed in one cell, separated by this character.
     */
    public const ROLE_SEPARATOR = ';';

    public static function clients(): ImportTemplate
    {
        return new ImportTemplate([
            new ImportColumn('id', __('imports.columns.id'), false, ''),
            new ImportColumn('name', __('clients.fields.name'), true, 'Example Client'),
            new ImportColumn('email', __('clients.fields.email'), true, 'client@example.com'),
            new ImportColumn('status', __('clients.fields.status'), true, ClientStatus::Active->value),
            new ImportColumn('notes', __('clients.fields.notes'), false, 'Optional note'),
        ]);
    }

    public static function users(): ImportTemplate
    {
        return new ImportTemplate([
            new ImportColumn('id', __('imports.columns.id'), false, ''),
            new ImportColumn('name', __('users.fields.name'), true, 'Example Person'),
            new ImportColumn('email', __('users.fields.email'), true, 'person@example.com'),
            new ImportColumn('password', __('users.fields.password'), false, 'ChangeMe123456'),
            new ImportColumn('is_active', __('users.fields.is_active'), false, '1'),
            new ImportColumn('roles', __('users.fields.roles'), false, 'Administrator'),
        ]);
    }
}
