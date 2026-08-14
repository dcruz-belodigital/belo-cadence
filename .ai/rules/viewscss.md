---
paths:
  - 'resources/{views,css}/**'
---

# Viewscss

## Semantic design tokens only, no raw palette colours
All visual decisions live in `resources/css/app.css`: semantic colour tokens defined twice (`:root` and `.dark`), typography via `--text-*`, shape via `--radius-*`/`--spacing-control*`, plus the shared `btn`, `form-control` and `focus-ring` utilities. Components consume tokens (`bg-surface`, `text-foreground-muted`, `btn btn-secondary btn-md`) and never Tailwind palette classes such as `bg-zinc-950`; the one deliberate exception is the white canvas of the delivery snapshot iframe. Theme is a user preference rendered by `partials/theme-script.blade.php` before first paint, so never move that script below the stylesheet. Add reusable markup as a Blade component only when it sets a visual standard — resource tables stay readable per resource rather than becoming a generic table engine.

## The visual language is flat, violet and softly rounded
The product deliberately does not look like a default Tailwind SaaS. These decisions carry that and should not be quietly undone:

- One violet family, one temperature. Every surface and accent sits near hue 285-300; the interface separates by lightness, never by warmth. An earlier version put a warm blush on the chrome against cool violet content and it read as a mistake rather than a decision — do not reintroduce a second temperature. `info` is deliberately teal so it cannot be mistaken for the primary.
- The primary is medium slate blue, deepened in light mode until white text on it is legible and lifted to periwinkle in dark, so `primary-foreground` is near-white in light and near-black in dark. `primary-soft` (thistle) is the selected state — the active navigation entry, the avatar.
- No elevation. `--cadence-shadow-card` is `none` in both themes; depth comes from hairline borders and the surface tones. Only `shadow-popover` is a real shadow.
- Generous corners. `--radius-control` is 10px and `--radius-card` 16px. `rounded-pill` is still only for dots, spinners and the tick.
- The tick. A 3px pill-ended vertical bar (`tick` utility, or the `::before` on `nav-link-active`) marks the current page and heads every card. It is the one ornament; do not invent a second. Anything drawn flush to a card edge must be inset (see `x-metric`) or the radius clips it into a taper.

Icons are Lucide, inlined in `x-icon`. The Lucide conventions — 24px box, 2px stroke, round caps and joins — are set once on the `<svg>`, so a new glyph is pasted in as bare paths with no per-path attributes. Names in `@case` are the application's own rather than Lucide's, so a glyph can be swapped without touching the views that ask for it. There is no icon package in `composer.json`; adding one would mean rewriting every call site.

Figures are set in `font-mono` with the `numeric` utility so columns of them line up. Instrument Sans ships at 400/500/600/700 only (see `vite.config.js`) — using any other weight gets a synthesised bold, so pick from those four.

An invalid `form-control` doubles its border rather than only recolouring it, so that an error still outranks the focus ring. Keep error states louder than focus.

## A page is one of exactly two widths, and the layout applies it
`x-app-layout` takes `width="wide"` (the default) or `width="narrow"` and puts the class on `<main>`. Wide (`--container-wide`, 90rem) is for tables, index pages and dashboards; narrow (`--container-narrow`, 48rem) is for forms and reading pages. Nothing else may set a page width — no `mx-auto max-w-*` on a page or on a component a page fills.

That is not tidiness. `x-page-header` renders the page description inside the layout's container, so a page that constrained itself left the description stranded at the edge of a wider box while its content sat centred in a narrower one. One container for both is what keeps them on the same left edge. `tests/Feature/DesignSystemTest.php` fails the build if a page reintroduces its own width.

A reading measure inside a page is a different thing and is allowed: `x-document` is a wide page because its contents list needs a column, and caps the prose beside it at `max-w-narrow` — reusing one of the two widths rather than inventing a third.

## Validation messages have exactly one style
Every validation message is rendered by `x-form.error` and nothing else: a plain `<p class="text-meta text-danger" role="alert">`, no icon, sitting under the thing that is wrong. There is deliberately no summary box at the top of a form — it repeated the same messages in a second style. `x-form.field`, `x-form.checkbox`, `x-form.input`/`select`/`textarea` are the only other views allowed to touch `$errors`, and only to set `aria-invalid`/`aria-describedby`.

Because nothing repeats the whole bag any more, every rule key needs a home or its failure is invisible — the form just returns unchanged. Keys belonging to no single field (a whole array such as `entries`/`mapping`/`schedules`, or rows a script renders client side) are covered by passing a list to `x-form.error`, which understands wildcards: `:name="['entries', 'entries.*']"`. A checkbox whose submitted name differs from its message key needs `error-key` (`schedules[0][is_enabled]` submits, `schedules.0.is_enabled` fails).

`tests/Feature/FormErrorTest.php` enforces this: it renders every form page with a message under every rule key of the Form Request behind it, and fails if one has nowhere to appear. Add the keys there when you add a rule.
