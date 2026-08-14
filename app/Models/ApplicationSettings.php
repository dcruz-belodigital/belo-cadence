<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsEmailAddress;
use App\Casts\AsTimezoneIdentifier;
use App\Enums\Locale;
use App\Enums\Theme;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Application-wide configuration held in a single row.
 *
 * Infrastructure credentials are deliberately absent: those stay in the environment.
 *
 * @property int $id
 * @property string $application_name
 * @property Locale $default_locale
 * @property TimezoneIdentifier $default_timezone
 * @property Theme $default_theme
 * @property string $client_email_sender_name
 * @property EmailAddress $client_email_sender_email
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable([
    'application_name',
    'default_locale',
    'default_timezone',
    'default_theme',
    'client_email_sender_name',
    'client_email_sender_email',
])]
final class ApplicationSettings extends Model
{
    /**
     * The stored settings, or the configured defaults when an environment has not
     * been seeded yet, so the application is never left without usable values.
     */
    public static function current(): self
    {
        return self::query()->orderBy('id')->first() ?? self::defaults();
    }

    /**
     * The settings an environment starts with, taken from configuration.
     */
    public static function defaults(): self
    {
        return new self([
            'application_name' => (string) config('app.name'),
            'default_locale' => (string) config('app.locale'),
            'default_timezone' => (string) config('app.timezone'),
            'default_theme' => (string) config('cadence.default_theme'),
            'client_email_sender_name' => (string) config('mail.from.name'),
            'client_email_sender_email' => (string) config('mail.from.address'),
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_locale' => Locale::class,
            'default_timezone' => AsTimezoneIdentifier::class,
            'default_theme' => Theme::class,
            'client_email_sender_email' => AsEmailAddress::class,
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
