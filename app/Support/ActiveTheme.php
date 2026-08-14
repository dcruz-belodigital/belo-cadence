<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Theme;
use App\Models\ApplicationSettings;
use App\Models\User;

/**
 * Which theme a page is drawn in.
 *
 * A reader who has chosen one gets it; a reader who has not follows the application
 * default, and so keeps following it when an administrator changes it later. Signed
 * out pages have nobody to ask, so they use the default too.
 *
 * The rule lives here rather than in each layout so both layouts, and anything added
 * later, cannot answer the question differently.
 */
final class ActiveTheme
{
    public static function for(?User $user): Theme
    {
        return $user?->theme ?? self::applicationDefault();
    }

    public static function applicationDefault(): Theme
    {
        return ApplicationSettings::current()->default_theme;
    }
}
