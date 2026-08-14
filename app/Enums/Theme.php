<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The visual identities the application ships with.
 *
 * A theme decides colour, shape and lettering together; the cases here are only the
 * names. Every value a theme sets lives in `resources/css/themes.css` as one block of
 * `--cadence-*` variables, keyed by this case's value through the `data-theme`
 * attribute on the document.
 *
 * Adding one means a case here, a label in `lang/en/enums.php`, and a block there.
 * Nothing else in the application needs to know a theme exists.
 *
 * This is not the light/dark switch: that is {@see ColorScheme}, and every theme
 * below is drawn in both.
 */
enum Theme: string
{
    case Iris = 'iris';
    case Cappuccino = 'cappuccino';
    case Bubblegum = 'bubblegum';
    case Graphite = 'graphite';
    case Cathode = 'cathode';

    /**
     * The theme an installation falls back to before anybody has chosen one.
     */
    public static function default(): self
    {
        return self::Iris;
    }

    public function label(): string
    {
        return __('enums.theme.'.$this->value.'.label');
    }

    /**
     * A sentence describing the theme, shown beside the name when choosing one.
     */
    public function description(): string
    {
        return __('enums.theme.'.$this->value.'.description');
    }
}
