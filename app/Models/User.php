<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsEmailAddress;
use App\Casts\AsTimezoneIdentifier;
use App\Data\Users\UserFilters;
use App\Enums\ColorScheme;
use App\Enums\Locale;
use App\Enums\Theme;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property EmailAddress $email
 * @property string $password
 * @property bool $is_active
 * @property ColorScheme $color_scheme
 * @property Theme|null $theme
 * @property Locale $locale
 * @property TimezoneIdentifier $timezone
 * @property string|null $remember_token
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Audit> $audits
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $unreadNotifications
 */
#[Fillable(['name', 'email', 'password', 'is_active', 'color_scheme', 'theme', 'locale', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The audit entries this user is the actor of.
     *
     * @return HasMany<Audit, $this>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    /**
     * The one or two letters shown in place of a picture: the first letter of the first
     * and last parts of the name, or just the first letter of a single-word name.
     */
    public function initials(): string
    {
        // Only parts that begin with a letter count, so a name such as
        // "Ada Demo (coordinator)" gives "AD" rather than "A(".
        $parts = array_values(array_filter(
            preg_split('/\s+/', trim($this->name)) ?: [],
            static fn (string $part): bool => preg_match('/^\p{L}/u', $part) === 1,
        ));

        if ($parts === []) {
            return '?';
        }

        $letters = count($parts) === 1
            ? mb_substr($parts[0], 0, 1)
            : mb_substr($parts[0], 0, 1).mb_substr((string) end($parts), 0, 1);

        return mb_strtoupper($letters);
    }

    /**
     * The email address notifications and password resets are sent to.
     *
     * Both are overridden because the attribute is cast to a value object rather
     * than a plain string.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email->value;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email->value;
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Applies the user table's search, filters and sorting.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function filtered(Builder $query, UserFilters $filters): void
    {
        $query
            ->when($filters->search, fn (Builder $users, string $search): Builder => $users->where(
                fn (Builder $match): Builder => $match
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($filters->role, fn (Builder $users, string $role): Builder => $users
                ->whereHas('roles', fn (Builder $roles): Builder => $roles->where('name', $role)))
            ->when($filters->isActive !== null, fn (Builder $users): Builder => $users
                ->where('is_active', $filters->isActive))
            ->orderBy($filters->sort, $filters->direction);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => AsEmailAddress::class,
            'password' => 'hashed',
            'is_active' => 'boolean',
            'color_scheme' => ColorScheme::class,
            'theme' => Theme::class,
            'locale' => Locale::class,
            'timezone' => AsTimezoneIdentifier::class,
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
