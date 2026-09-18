---
paths:
  - 'public/*.svg'
---

# Public

## The favicon SVG is parsed as strict XML, so no double hyphen in its comment
`public/favicon.svg` is loaded as an image document, not as HTML: any XML error stops it rendering entirely and the browser drops silently to `favicon-32.png`, taking the theme colour with it. Nothing reports the failure — the tab just shows the fixed PNG.

This already happened once: the comment named the `cadence-primary` custom property with its leading dashes, and `--` is illegal inside an XML comment. Write custom properties without the dashes in that comment, and never let `--` appear in it.

`tests/Feature/BrandingTest.php` now parses the file with `DOMDocument::loadXML` and fails on any XML error, so run `php artisan test --filter=Branding` after editing it. `xmllint --noout public/favicon.svg` is the quick check.
