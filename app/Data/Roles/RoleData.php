<?php

declare(strict_types=1);

namespace App\Data\Roles;

/**
 * A role and the permissions it grants.
 *
 * Permission names are always drawn from the PermissionName enum: the interface offers
 * no way to invent one.
 */
final readonly class RoleData
{
    /**
     * @param  list<string>  $permissionNames
     */
    public function __construct(
        public string $name,
        public array $permissionNames,
    ) {}
}
