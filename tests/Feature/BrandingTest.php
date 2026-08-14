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

it('keeps the tab icon neutral rather than tied to a theme', function (): void {
    $favicon = (string) file_get_contents(public_path('favicon.svg'));

    // A tab sits in the browser's chrome, so the mark inverts with the browser, not the app.
    expect($favicon)->toContain('prefers-color-scheme: dark');
});
