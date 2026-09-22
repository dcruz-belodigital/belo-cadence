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
 * The order below is the order a reader is offered them, because both pickers build
 * themselves from `cases()`. It runs from the plainest to the most characterful — the
 * default first, then the themes that could pass for any business application, and the
 * ones with an opinion about lettering last. Put a new case where it belongs on that
 * run rather than at the end.
 *
 * This is not the light/dark switch: that is {@see ColorScheme}, and every theme
 * below is drawn in both.
 */
enum Theme: string
{
    case Iris = 'iris';
    case Graphite = 'graphite';
    case Cappuccino = 'cappuccino';
    case Bubblegum = 'bubblegum';
    case Meridian = 'meridian';
    case Ember = 'ember';
    case Folio = 'folio';
    case Cathode = 'cathode';
    case Manuscript = 'manuscript';

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
