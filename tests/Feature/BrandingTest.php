<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * The mark is a folded paper plane, drawn once as a Blade component and once as a static
 * file for the browser tab. The two are kept in step by hand, so what is checked here is
 * that both exist, that both are reachable, and that the in-application one is still
 * colourless enough to take the theme.
 */
it('ships every icon the tab and the home screen ask for', function (string $file): void {
    expect(public_path($file))->toBeFile();
})->with(['favicon.svg', 'favicon-32.png', 'apple-touch-icon.png']);

it('links the icons from a signed in page', function (): void {
    $page = actingAs(administrator())->get(route('dashboard'))->assertOk();

    expect($page->getContent())
        ->toContain('rel="icon"')
        ->toContain('favicon.svg')
        ->toContain('rel="apple-touch-icon"');
});

it('links the icons from the sign in page', function (): void {
    expect(get(route('login'))->assertOk()->getContent())->toContain('favicon.svg');
});

/*
| The mark takes its colour from whatever it is set in, which is the whole reason the
| logo follows the theme without a copy per theme. A hard-coded fill would freeze it.
*/
it('draws the mark in the current colour rather than a fixed one', function (): void {
    $component = (string) file_get_contents(resource_path('views/components/logo.blade.php'));

    expect($component)
        ->toContain('currentColor')
        ->and($component)->not->toContain('#');
});

it('shows the mark on the sign in screen and in the sidebar', function (): void {
    // Both sit on the primary tile, so the plane is knocked out of the theme's own colour.
    expect(get(route('login'))->assertOk()->getContent())->toContain('bg-primary text-primary-foreground');

    expect(actingAs(administrator())->get(route('dashboard'))->assertOk()->getContent())
        ->toContain('bg-primary text-primary-foreground');
});

/*
| The tab icon is drawn in the theme's primary, which the browser cannot work out on its
| own: a favicon is a separate document and cannot read the page's variables. So the file
| ships a neutral that inverts with the browser's own appearance, and favicon.js swaps in
| `--cadence-primary` read off the live document. That swap is one `color` declaration and
| only works while every part of the mark inks from it.
*/
it('inks the whole tab icon from one colour so the theme can replace it', function (): void {
    $favicon = (string) file_get_contents(public_path('favicon.svg'));

    expect($favicon)
        ->toContain('svg { color:')
        ->toContain('currentColor')
        ->toContain('prefers-color-scheme: dark')
        ->and($favicon)->not->toContain('fill: #')
        ->and($favicon)->not->toContain('stroke: #');
});

/*
| A favicon is parsed as strict XML rather than as HTML, so a mistake no HTML parser would
| mind stops the whole document rendering and the browser drops silently to the PNG, taking
| the theme with it. That happened once: a double hyphen inside the comment, from naming the
| `cadence-primary` custom property with its leading dashes. Nothing in the file announces
| the failure, so it is parsed here instead of only read.
*/
it('parses as the strict XML a favicon is read as', function (): void {
    $previous = libxml_use_internal_errors(true);

    $document = new DOMDocument;
    $parsed = $document->loadXML((string) file_get_contents(public_path('favicon.svg')));
    $errors = libxml_get_errors();

    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    expect($parsed)->toBeTrue(
        'public/favicon.svg is not well-formed XML: '.implode(' ', array_map(
            static fn (LibXMLError $error): string => trim($error->message).' (line '.$error->line.')',
            $errors,
        ))
    );
});

it('repaints the tab icon from the theme rather than listing the palette again', function (): void {
    $script = (string) file_get_contents(resource_path('js/favicon.js'));

    expect($script)
        ->toContain('--cadence-primary')
        ->and($script)->not->toContain('oklch');
});
