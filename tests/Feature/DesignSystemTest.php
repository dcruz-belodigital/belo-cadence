<?php

declare(strict_types=1);

/**
 * Tailwind decides what to compile by reading the source files as plain text, so a class
 * name that is assembled at runtime silently produces no styling at all — a button with
 * no colour, and nothing in any HTTP response to reveal it.
 *
 * These tests read the compiled stylesheet and insist the design system is really there.
 */
function compiledStylesheet(): string
{
    $manifestPath = public_path('build/manifest.json');

    if (! file_exists($manifestPath)) {
        test()->markTestSkipped('Front-end assets have not been built. Run "npm run build".');
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $file = $manifest['resources/css/app.css']['file'] ?? null;

    if (! is_string($file)) {
        test()->markTestSkipped('The build manifest does not contain the application stylesheet.');
    }

    return (string) file_get_contents(public_path('build/'.$file));
}

/**
 * Every Blade file in the application, whatever its depth. `glob()` does not recurse and
 * `+` on two lists keeps only the longer one's extra entries, so the listing is merged.
 *
 * @return list<string>
 */
function everyViewFile(): array
{
    return array_merge(
        glob(resource_path('views/*.blade.php')) ?: [],
        glob(resource_path('views/*/*.blade.php')) ?: [],
        glob(resource_path('views/*/*/*.blade.php')) ?: [],
        glob(resource_path('views/*/*/*/*.blade.php')) ?: [],
    );
}

function stylesheetDefines(string $utility): bool
{
    // Matches ".btn-primary" but not ".btn-primary-something", and allows the escaped
    // variant prefixes Tailwind writes, such as ".hover\:bg-surface-hover".
    return (bool) preg_match('/\.(?:[\w-]+\\\\:)*'.preg_quote($utility, '/').'(?![\w-])/', compiledStylesheet());
}

it('compiles every button variant and size the component can produce', function (string $utility): void {
    expect(stylesheetDefines($utility))->toBeTrue(
        "The [{$utility}] utility is missing from the compiled stylesheet, so anything using it renders unstyled."
    );
})->with([
    'btn', 'btn-primary', 'btn-secondary', 'btn-danger', 'btn-ghost', 'btn-md', 'btn-sm', 'btn-icon',
]);

it('compiles the shared form and focus utilities', function (string $utility): void {
    expect(stylesheetDefines($utility))->toBeTrue("The [{$utility}] utility is missing from the compiled stylesheet.");
})->with([
    'form-control', 'focus-ring', 'focus-ring-inset', 'user-menu-trigger',
    'field-with-action', 'field-action', 'field-action-label',
]);

it('compiles the typography scale', function (string $utility): void {
    expect(stylesheetDefines($utility))->toBeTrue("The [{$utility}] utility is missing from the compiled stylesheet.");
})->with([
    'text-page-title', 'text-section-title', 'text-metric', 'text-body', 'text-meta', 'text-label', 'text-overline',
]);

it('compiles the shape and layout tokens', function (string $utility): void {
    expect(stylesheetDefines($utility))->toBeTrue("The [{$utility}] utility is missing from the compiled stylesheet.");
})->with([
    'rounded-control', 'rounded-card', 'rounded-pill', 'size-control-sm',
    'w-sidebar', 'max-w-wide', 'max-w-narrow', 'shadow-card', 'shadow-popover',
]);

/*
| A page that sets its own width leaves the description above it stranded at the edge
| of a wider container, which is the misalignment this pair of widths exists to stop.
*/
it('never lets a page set its own width', function (): void {
    $offenders = [];

    foreach (everyViewFile() as $file) {
        $source = (string) file_get_contents($file);

        if (! str_contains($source, '<x-app-layout')) {
            continue;
        }

        if (preg_match('/class="[^"]*\bmx-auto\b[^"]*\bmax-w-/', $source) === 1) {
            $offenders[] = basename($file);
        }
    }

    expect($offenders)->toBe([], 'These pages constrain their own width instead of asking the layout for one: '.implode(', ', $offenders));
});

it('compiles the semantic colour tokens', function (string $utility): void {
    expect(stylesheetDefines($utility))->toBeTrue("The [{$utility}] utility is missing from the compiled stylesheet.");
})->with([
    'bg-background', 'bg-surface', 'bg-surface-muted', 'bg-surface-hover', 'bg-surface-elevated', 'bg-overlay',
    'text-foreground', 'text-foreground-muted', 'text-foreground-subtle', 'text-danger', 'text-primary',
    'border-border', 'border-border-strong', 'divide-border', 'accent-primary',
    'bg-primary', 'bg-primary-soft', 'bg-success-soft', 'bg-warning-soft', 'bg-danger-soft', 'bg-info-soft', 'bg-neutral-soft',
]);

it('ships a dark palette as well as a light one', function (): void {
    $css = compiledStylesheet();

    expect($css)->toContain('--cadence-background')
        ->and($css)->toContain('.dark');
});

it('never assembles a class name at runtime in a component', function (): void {
    $offenders = [];

    foreach (array_filter(everyViewFile(), fn (string $file): bool => str_contains($file, '/views/components/')) as $file) {
        foreach (explode("\n", (string) file_get_contents($file)) as $line) {
            // A quoted class fragment glued to a variable, such as 'btn-'.$variant. Only
            // lines that are about classes count: an id built the same way is harmless.
            $aboutClasses = str_contains(strtolower($line), 'class');

            if ($aboutClasses && preg_match("/'[a-z][\w-]*-'\s*\.\s*\\$/", $line) === 1) {
                $offenders[] = basename($file);

                break;
            }
        }
    }

    expect($offenders)->toBe([], 'These components build class names at runtime, which Tailwind cannot see: '.implode(', ', $offenders));
});

/*
| Every action a row offers sits in its menu. A loose button in an action cell is how a
| table grows back into a wall of repeated controls, and nothing else fails while it does:
| the markup still renders, and every page test still passes.
*/
it('keeps a table row action inside the row menu', function (): void {
    $offenders = [];

    foreach (everyViewFile() as $file) {
        $source = (string) file_get_contents($file);

        preg_match_all('/<x-table\.cell[^>]*\balign="right"[^>]*>(.*?)<\/x-table\.cell>/s', $source, $matches);

        foreach ($matches[1] as $cell) {
            if (str_contains($cell, '<x-button')) {
                $offenders[] = basename($file);

                break;
            }
        }
    }

    expect($offenders)->toBe([], 'These tables put a loose button in a row instead of an entry in x-table.actions: '.implode(', ', $offenders));
});
