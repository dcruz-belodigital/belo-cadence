---
paths:
  - 'lang/**'
---

# Lang

## Every locale is a full catalogue, and the docs ship per language too
The application ships English and European Portuguese (`App\Enums\Locale`). English is canonical: it is what the source strings are written in and what `app.fallback_locale` points at.

That fallback is the trap. A key missing from `lang/pt` does not error — it silently renders in English, so a half-translated page looks deliberate. `tests/Feature/LocaleTest.php` compares the flattened key sets in both directions and fails the build on either a missing key or an unknown extra one. When you add a string, add it to every locale in the same commit.

The guide and changelog are translated too. They live in `resources/docs/<locale>/` and are opened through `App\Support\Documentation\DocumentLibrary`, never by a hard-coded path — it falls back to `docs/en` for a language that has not written that page yet.

Adding a language: a case in the enum with a `flag()`, a `lang/<value>` directory, a `resources/docs/<value>` directory, and a label under `enums.locale` in *every* existing catalogue. The account-menu switcher and both select fields build themselves from `Locale::cases()`.

Date formats are localised as well — `common.formats.*` is d/m/Y in Portuguese, not the English d M Y.
