---
paths:
  - 'app/Models/**'
---

# Models

## Model conventions: casts, morph map, trashed relations
Models are `final`, carry complete PHPDoc, declare every castable attribute in `casts()` (enums, `immutable_datetime`, `AsEmailAddress`, `AsTimezoneIdentifier`) and use `#[Fillable]`/`#[Hidden]` attributes rather than properties. `Relation::enforceMorphMap()` in AppServiceProvider means `auditable_type` and `model_type` store short keys such as `client` — add new models to that map or morphs will throw. Relations that point at `Client` from schedules and deliveries use `->withTrashed()` so history stays readable after a client is archived; queries that decide whether an email may be sent therefore state `whereNull('deleted_at')` explicitly (see the `due` scope). Table search/filter/sort logic lives in one `#[Scope] filtered(Builder, XFilters)` per model so a table and its CSV export can never disagree.
