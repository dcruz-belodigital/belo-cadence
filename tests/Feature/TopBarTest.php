<?php

declare(strict_types=1);

use App\Enums\ColorScheme;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * The top bar carries the notification bell and the account button. The theme control
 * lives inside the account menu, not beside it.
 */
it('shows the account button with the reader name and initial', function (): void {
    $user = administrator(['name' => 'Ada Demo']);

    $page = actingAs($user)->get(route('dashboard'))->assertOk();

    expect($page->getContent())
        ->toContain('user-menu-trigger')
        ->toContain('Ada Demo')
        // The avatar shows the reader's initials. Its shape is the design system's to decide.
        ->toMatch('/bg-primary-soft[^>]*>\s*AD\s*<\/span>/');
});

it('keeps the notification bell as its own button in the top bar', function (): void {
    $page = actingAs(administrator())->get(route('dashboard'))->assertOk();

    expect($page->getContent())->toContain('aria-label="'.__('navigation.notifications').'"');
});

it('offers the theme control inside the account menu', function (): void {
    $page = actingAs(administrator())->get(route('dashboard'))->assertOk();

    $content = $page->getContent();

    expect($content)->toContain('aria-label="'.__('settings.color_scheme.label').'"');

    foreach (ColorScheme::cases() as $theme) {
        expect($content)->toContain('value="'.$theme->value.'"');
    }
});

it('marks the reader current theme as the chosen one', function (): void {
    $page = actingAs(administrator(['color_scheme' => ColorScheme::Dark]))
        ->get(route('dashboard'))
        ->assertOk();

    /*
    | Exactly one segment of the colour-scheme group is pressed, and it is the stored
    | preference. The count is taken within that group because the account menu holds a
    | second group of the same shape for the language.
    */
    $group = Str::betweenFirst(
        $page->getContent(),
        'aria-label="'.__('settings.color_scheme.label').'"',
        '</div>',
    );

    expect(substr_count($group, 'aria-pressed="true"'))->toBe(1)
        ->and($group)->toContain('aria-pressed="true"'."\n".'                        title="'.ColorScheme::Dark->label().'"');
});

it('changes the theme from the account menu', function (): void {
    $user = administrator(['color_scheme' => ColorScheme::System, 'timezone' => 'Europe/Lisbon']);

    actingAs($user)
        ->patch(route('profile.color-scheme.update'), ['color_scheme' => ColorScheme::Dark->value])
        ->assertRedirect();

    $user->refresh();

    expect($user->color_scheme)->toBe(ColorScheme::Dark)
        ->and($user->timezone->value)->toBe('Europe/Lisbon');

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('const preference = "dark"', escape: false);
});

it('no longer renders a separate theme button beside the account menu', function (): void {
    $page = actingAs(administrator())->get(route('dashboard'))->assertOk();

    // The theme control is a group inside the menu, never a dropdown of its own.
    expect(substr_count($page->getContent(), 'aria-label="'.__('settings.color_scheme.label').'"'))->toBe(1);
});
