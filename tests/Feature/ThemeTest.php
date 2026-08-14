<?php

declare(strict_types=1);

use App\Enums\Theme;
use App\Models\ApplicationSettings;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * A theme is a name in an enum, a block of variables in themes.css and a label in the
 * translations. Those three can drift apart silently — the page would simply render
 * with the default's colours — so most of what is checked here is that they have not.
 */
function themeStylesheet(): string
{
    return (string) file_get_contents(resource_path('css/themes.css'));
}

/**
 * The variable names a selector declares, so two blocks can be compared.
 *
 * @return list<string>
 */
function declaredVariables(string $css, string $selector): array
{
    $start = mb_strpos($css, $selector.' {');

    if ($start === false) {
        return [];
    }

    $block = mb_substr($css, $start, (int) mb_strpos($css, '}', $start) - $start);

    preg_match_all('/(--cadence-[a-z-]+):/', $block, $matches);

    $names = $matches[1];
    sort($names);

    return $names;
}

/**
 * Every theme but the default, which lives in app.css rather than in themes.css.
 *
 * @return list<Theme>
 */
function alternativeThemes(): array
{
    return array_values(array_filter(Theme::cases(), fn (Theme $theme): bool => $theme !== Theme::default()));
}

describe('the theme catalogue', function (): void {
    it('has a name and a description for every theme', function (Theme $theme): void {
        expect($theme->label())->not->toBe('')->and($theme->label())->not->toContain('.')
            ->and($theme->description())->not->toBe('')->and($theme->description())->not->toContain('enums.');
    })->with(Theme::cases());

    it('styles every theme except the default, which lives in app.css', function (Theme $theme): void {
        if ($theme === Theme::default()) {
            expect(file_get_contents(resource_path('css/app.css')))->toContain('--cadence-radius-card');

            return;
        }

        expect(themeStylesheet())
            ->toContain(":root[data-theme='{$theme->value}']")
            ->toContain(":root.dark[data-theme='{$theme->value}']");
    })->with(Theme::cases());

    /*
    | A variable set in a theme's light block but not its dark one keeps the light
    | value on a dark page, which is how a theme ends up with white-on-white.
    */
    it('declares the same variables in a theme light and dark blocks', function (Theme $theme): void {
        $css = themeStylesheet();

        expect(declaredVariables($css, ":root.dark[data-theme='{$theme->value}']"))
            ->toBe(declaredVariables($css, ":root[data-theme='{$theme->value}']"));
    })->with(alternativeThemes());

    it('gives every theme its own colour, shape and lettering', function (Theme $theme): void {
        expect(declaredVariables(themeStylesheet(), ":root[data-theme='{$theme->value}']"))
            ->toContain('--cadence-background')
            ->toContain('--cadence-primary')
            ->toContain('--cadence-radius-card')
            ->toContain('--cadence-font-sans');
    })->with(alternativeThemes());
});

describe('choosing a theme', function (): void {
    it('draws the page in the application default when nobody has chosen', function (): void {
        ApplicationSettings::defaults()->fill(['default_theme' => Theme::Graphite])->save();

        actingAs(administrator(['theme' => null]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme="graphite"', escape: false);
    });

    it('prefers the reader own theme over the application default', function (): void {
        ApplicationSettings::defaults()->fill(['default_theme' => Theme::Graphite])->save();

        actingAs(administrator(['theme' => Theme::Cathode]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme="cathode"', escape: false);
    });

    /*
    | Following the default means following it as it changes, which is the whole point
    | of storing nothing rather than storing today's default.
    */
    it('follows the application default when it changes afterwards', function (): void {
        $user = administrator(['theme' => null]);

        ApplicationSettings::defaults()->fill(['default_theme' => Theme::Bubblegum])->save();

        actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('data-theme="bubblegum"', escape: false);
    });

    it('draws signed out pages in the application default', function (): void {
        ApplicationSettings::defaults()->fill(['default_theme' => Theme::Cappuccino])->save();

        get(route('login'))->assertOk()->assertSee('data-theme="cappuccino"', escape: false);
    });

    it('stores a theme chosen in the profile', function (): void {
        $user = administrator(['theme' => null]);

        actingAs($user)
            ->put(route('profile.preferences.update'), [
                'color_scheme' => 'system',
                'theme' => Theme::Cappuccino->value,
                'locale' => 'en',
                'timezone' => 'UTC',
            ])
            ->assertRedirect();

        expect($user->refresh()->theme)->toBe(Theme::Cappuccino);
    });

    it('lets the reader go back to following the application', function (): void {
        $user = administrator(['theme' => Theme::Bubblegum]);

        actingAs($user)
            ->put(route('profile.preferences.update'), [
                'color_scheme' => 'system',
                'theme' => '',
                'locale' => 'en',
                'timezone' => 'UTC',
            ])
            ->assertRedirect();

        expect($user->refresh()->theme)->toBeNull();
    });

    it('refuses a theme that does not exist', function (): void {
        actingAs(administrator())
            ->put(route('profile.preferences.update'), [
                'color_scheme' => 'system',
                'theme' => 'vantablack',
                'locale' => 'en',
                'timezone' => 'UTC',
            ])
            ->assertSessionHasErrors('theme');
    });

    /* The light/dark switch in the top bar writes preferences too, and must not wipe this one. */
    it('keeps the chosen theme when the colour scheme is switched', function (): void {
        $user = administrator(['theme' => Theme::Cathode]);

        actingAs($user)
            ->patch(route('profile.color-scheme.update'), ['color_scheme' => 'dark'])
            ->assertRedirect();

        expect($user->refresh()->theme)->toBe(Theme::Cathode);
    });

    it('offers every theme on the profile page', function (): void {
        $page = actingAs(administrator())->get(route('profile.edit'))->assertOk();

        foreach (Theme::cases() as $theme) {
            expect($page->getContent())->toContain('value="'.$theme->value.'"');
        }
    });

    it('offers every theme in the application settings', function (): void {
        $page = actingAs(administrator())->get(route('admin.settings.edit'))->assertOk();

        foreach (Theme::cases() as $theme) {
            expect($page->getContent())->toContain('value="'.$theme->value.'"');
        }
    });
});
