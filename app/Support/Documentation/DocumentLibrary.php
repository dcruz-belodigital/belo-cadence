<?php

declare(strict_types=1);

namespace App\Support\Documentation;

use App\Enums\Locale;
use Illuminate\Support\Facades\App;

/**
 * Finds a document in the reader's language.
 *
 * Documents live in `resources/docs/<locale>`. A language that has not been written yet,
 * or has fallen behind and is missing a page, falls back to English rather than showing
 * an error: an out-of-date guide is more use than no guide.
 */
final class DocumentLibrary
{
    public static function open(string $name): MarkdownDocument
    {
        return MarkdownDocument::fromFile(self::pathFor($name));
    }

    /**
     * The best available file for the current locale.
     */
    public static function pathFor(string $name, ?string $locale = null): string
    {
        $locale ??= App::getLocale();

        $localised = resource_path("docs/{$locale}/{$name}.md");

        return is_file($localised)
            ? $localised
            : resource_path('docs/'.Locale::English->value."/{$name}.md");
    }
}
