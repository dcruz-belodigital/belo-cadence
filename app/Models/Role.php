<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * The application's own role model, so roles get a first-party policy,
 * route binding and factory like every other resource.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property int|null $users_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, User> $users
 */
final class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
