---
paths:
  - 'resources/css/**'
---

# Css

## Themes are one block of --cadence-* variables, and nothing else
The application ships several named themes (App\Enums\Theme). A theme sets colour, corner radius and lettering together, and is drawn in both light and dark.

How it works, and why it must stay this way:
- `@theme inline` in app.css never holds a literal value. Every Tailwind token points at a `--cadence-*` variable, which is what makes `rounded-card` compile to `var(--cadence-radius-card)` rather than to `1rem`. Put a literal back in `@theme` and that property stops being themeable — silently, because the default still looks right.
- Iris is the default and lives in app.css as `:root` / `.dark`. It is also the fallback for anything a theme does not override.
- Every other theme is one block in `resources/css/themes.css`, written `:root[data-theme='x']` and `:root.dark[data-theme='x']`. The `:root` prefix is load-bearing: CSS pins `@import` to the top of the file, so themes.css is parsed before those defaults and must win on specificity rather than source order.
- The document carries `data-theme` server-rendered from `App\Support\ActiveTheme` (the reader's choice, else the application default). No flash, no script.

To add a theme: a case in the enum, a label and description in `lang/en/enums.php`, and a block in themes.css. Nothing else — no utility, no component, no Blade view.

`tests/Feature/ThemeTest.php` enforces the parts that drift silently: every case has a block, every dark block declares exactly the variables its light block does (a token set in only one leaks the wrong value across the switch), and every case has a translated name.

Fonts come from the `bunny()` entries in `vite.config.js`, one family per theme. Browsers fetch only the faces a page actually paints, so unselected themes cost nothing.

Note the vocabulary: "theme" is the look; the light/dark switch is `App\Enums\ColorScheme` and the `users.color_scheme` column.
