<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Locales the application ships translations for.
 *
 * English is the canonical locale: it is the one the source strings are written in and
 * the one Laravel falls back to when a key is missing. Further locales are added as
 * cases together with a `lang/<value>` directory and a `resources/docs/<value>` one.
 */
enum Locale: string
{
    case English = 'en';
    case Portuguese = 'pt';

    public function label(): string
    {
        return __('enums.locale.'.$this->value);
    }

    /**
     * A flag for the language, so the switcher can be recognised before it is read.
     */
    public function flag(): string
    {
        return match ($this) {
            self::English => '🇬🇧',
            self::Portuguese => '🇵🇹',
        };
    }
}
