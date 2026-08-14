<?php

declare(strict_types=1);

namespace App\Support\Documentation;

use App\Exceptions\MissingDocumentException;
use Illuminate\Support\Str;

/**
 * A page written as markdown and kept in `resources/docs`.
 *
 * The document is split on its second-level headings so the interface can offer a
 * contents list beside it, and each section is rendered on its own. Keeping the text in
 * markdown means it can be edited, reviewed in a diff, or published somewhere else
 * without touching the application.
 */
final readonly class MarkdownDocument
{
    /**
     * @param  list<DocumentSection>  $sections
     */
    public function __construct(
        public string $introHtml,
        public array $sections,
    ) {}

    public static function fromFile(string $path): self
    {
        if (! is_file($path)) {
            throw MissingDocumentException::at($path);
        }

        return self::fromMarkdown((string) file_get_contents($path));
    }

    public static function fromMarkdown(string $markdown): self
    {
        // A leading first-level heading is the page's own name, which the interface
        // already shows in the top bar.
        $markdown = (string) preg_replace('/\A\s*#\s+.*\R+/', '', $markdown);

        $parts = preg_split('/^##\s+(.*)$/m', $markdown, flags: PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return new self('', []);
        }

        $intro = trim(array_shift($parts) ?? '');
        $sections = [];

        foreach (array_chunk($parts, 2) as $chunk) {
            $heading = trim($chunk[0] ?? '');

            if ($heading === '') {
                continue;
            }

            $sections[] = new DocumentSection(
                id: Str::slug($heading),
                heading: $heading,
                html: (string) Str::markdown(trim($chunk[1] ?? '')),
            );
        }

        return new self(
            introHtml: $intro === '' ? '' : (string) Str::markdown($intro),
            sections: $sections,
        );
    }
}
