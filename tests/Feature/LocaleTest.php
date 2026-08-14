<?php

declare(strict_types=1);

use App\Enums\Locale;
use App\Models\ApplicationSettings;
use App\Support\Documentation\DocumentLibrary;
use App\Support\Documentation\MarkdownDocument;

use function Pest\Laravel\actingAs;

/**
 * Every key of the English catalogue, flattened, so two languages can be compared.
 *
 * @return list<string>
 */
function translationKeys(string $locale): array
{
    $keys = [];

    foreach (glob(lang_path($locale.'/*.php')) as $file) {
        $group = basename($file, '.php');

        $walk = function (array $lines, string $prefix) use (&$walk, &$keys): void {
            foreach ($lines as $key => $value) {
                is_array($value)
                    ? $walk($value, $prefix.$key.'.')
                    : $keys[] = $prefix.$key;
            }
        };

        $walk(require $file, $group.'.');
    }

    sort($keys);

    return $keys;
}

/**
 * Every locale but English, which is the one the others are compared against.
 *
 * @return list<Locale>
 */
function translatedLocales(): array
{
    return array_values(array_filter(Locale::cases(), fn (Locale $locale): bool => $locale !== Locale::English));
}

describe('the languages the application ships', function (): void {
    it('has a catalogue for every locale', function (Locale $locale): void {
        expect(lang_path($locale->value))->toBeDirectory();
    })->with(Locale::cases());

    it('has a name and a flag for every locale', function (Locale $locale): void {
        expect($locale->label())->not->toBe('')->and($locale->label())->not->toContain('enums.')
            ->and($locale->flag())->not->toBe('');
    })->with(Locale::cases());

    /*
    | A key present in English and missing in another language silently falls back to
    | English, so a half-translated page looks deliberate. This is what catches that.
    */
    it('translates every English key into every other language', function (Locale $locale): void {
        $missing = array_values(array_diff(translationKeys(Locale::English->value), translationKeys($locale->value)));

        expect($missing)->toBe([], "Missing {$locale->value} translations: ".implode(', ', array_slice($missing, 0, 20)));
    })->with(translatedLocales());

    it('has no key in another language that English does not have', function (Locale $locale): void {
        $extra = array_values(array_diff(translationKeys($locale->value), translationKeys(Locale::English->value)));

        expect($extra)->toBe([], "Unknown {$locale->value} keys: ".implode(', ', array_slice($extra, 0, 20)));
    })->with(translatedLocales());

    it('writes the guide and the changelog in every language', function (Locale $locale): void {
        foreach (['user-guide', 'changelog'] as $document) {
            expect(resource_path("docs/{$locale->value}/{$document}.md"))->toBeFile();
        }
    })->with(Locale::cases());

    /*
    | The import pages link into the guide by heading slug. Reword that heading in one
    | language and the link quietly lands nowhere, so the two are checked against each
    | other here rather than trusted to stay in step.
    */
    it('links the import pages at a heading the guide actually has', function (Locale $locale): void {
        $guide = MarkdownDocument::fromFile(DocumentLibrary::pathFor('user-guide', $locale->value));

        $anchor = __('imports.guide_anchor', locale: $locale->value);

        expect(collect($guide->sections)->pluck('id'))->toContain($anchor);
    })->with(Locale::cases());
});

describe('choosing a language', function (): void {
    it('renders the interface in the reader own language', function (): void {
        $page = actingAs(administrator(['locale' => Locale::Portuguese]))
            ->get(route('dashboard'))
            ->assertOk();

        expect($page->getContent())->toContain(__('dashboard.title', locale: 'pt'));
    });

    it('switches language from the account menu', function (): void {
        $user = administrator(['locale' => Locale::English]);

        actingAs($user)
            ->patch(route('profile.locale.update'), ['locale' => Locale::Portuguese->value])
            ->assertRedirect();

        expect($user->refresh()->locale)->toBe(Locale::Portuguese);
    });

    it('leaves the reader other preferences alone when switching language', function (): void {
        $user = administrator(['locale' => Locale::English, 'timezone' => 'Europe/Lisbon']);
        $scheme = $user->color_scheme;

        actingAs($user)->patch(route('profile.locale.update'), ['locale' => 'pt'])->assertRedirect();

        $user->refresh();

        expect($user->timezone->value)->toBe('Europe/Lisbon')
            ->and($user->color_scheme)->toBe($scheme);
    });

    it('refuses a language it does not ship', function (): void {
        actingAs(administrator())
            ->patch(route('profile.locale.update'), ['locale' => 'martian'])
            ->assertSessionHasErrors('locale');
    });

    it('offers every language in the account menu', function (): void {
        $page = actingAs(administrator())->get(route('dashboard'))->assertOk();

        foreach (Locale::cases() as $locale) {
            expect($page->getContent())->toContain('value="'.$locale->value.'"');
        }
    });

    it('reads the guide in the reader own language', function (): void {
        expect(DocumentLibrary::pathFor('user-guide', 'pt'))->toEndWith('docs/pt/user-guide.md');
    });

    /* An unwritten language is better served an English page than an error. */
    it('falls back to the English guide for a language that has none', function (): void {
        expect(DocumentLibrary::pathFor('user-guide', 'martian'))->toEndWith('docs/en/user-guide.md');
    });

    it('uses the application default language for signed out pages', function (): void {
        ApplicationSettings::defaults()->fill(['default_locale' => Locale::Portuguese])->save();

        $this->get(route('login'))->assertOk()->assertSee(__('auth.login.submit', locale: 'pt'), escape: false);
    });
});
