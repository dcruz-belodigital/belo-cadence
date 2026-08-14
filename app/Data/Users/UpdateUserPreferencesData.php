<?php

declare(strict_types=1);

namespace App\Data\Users;

use App\Enums\Locale;
use App\Enums\Theme;
use App\Enums\ColorScheme;
use App\ValueObjects\TimezoneIdentifier;

final readonly class UpdateUserPreferencesData
{
    public function __construct(
        public ColorScheme $colorScheme,
        /** Null follows whatever the application default is, now and later. */
        public ?Theme $theme,
        public Locale $locale,
        public TimezoneIdentifier $timezone,
    ) {}
}
