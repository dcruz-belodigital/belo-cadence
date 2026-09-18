<?php

declare(strict_types=1);

namespace App\Support\Csv;

use App\Enums\ClientStatus;
use App\Models\ClientAttribute;

/**
 * The importable CSV shapes this application understands.
 *
 * The fixed columns are a catalogue: adding one means changing it here, and the import,
 * the reference table, the column-matching step and the downloadable template all follow.
 *
 * The client shape also carries one column per *active* custom attribute, read here
 * rather than passed in, so all five callers cannot end up disagreeing about which
 * attributes exist — a disagreement that would validate a column the matching step never
 * offered. `ApplicationSettings::current()` is the same idea.
 */
final class ImportTemplates
{
    /**
     * Roles are listed in one cell, separated by this character.
     */
    public const ROLE_SEPARATOR = ';';

    /**
     * The prefix an attribute's column name carries, so it can never shadow a fixed one.
     */
    public const ATTRIBUTE_PREFIX = 'attribute_';

    public static function clients(): ImportTemplate
    {
        $attributes = ClientAttribute::query()->active()->get();

        return new ImportTemplate([
            new ImportColumn('id', __('imports.columns.id'), false, ''),
            new ImportColumn('name', __('clients.fields.name'), true, 'Example Client'),
            new ImportColumn('email', __('clients.fields.email'), true, 'client@example.com'),
            new ImportColumn('status', __('clients.fields.status'), true, ClientStatus::Active->value),
            new ImportColumn('notes', __('clients.fields.notes'), false, 'Optional note'),
            /*
            | Never required, even when the attribute is. A file that only means to
            | correct email addresses would otherwise be refused outright; a mapped cell
            | left blank for a required attribute still fails, on its own line.
            */
            ...$attributes->map(fn (ClientAttribute $attribute): ImportColumn => new ImportColumn(
                self::ATTRIBUTE_PREFIX.$attribute->key,
                $attribute->name,
                false,
                $attribute->csvExample(),
            ))->all(),
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
