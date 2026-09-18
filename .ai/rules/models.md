---
paths:
  - 'app/Models/**'
---

# Models

## Model conventions: casts, morph map, trashed relations
Models are `final`, carry complete PHPDoc, declare every castable attribute in `casts()` (enums, `immutable_datetime`, `AsEmailAddress`, `AsTimezoneIdentifier`) and use `#[Fillable]`/`#[Hidden]` attributes rather than properties. `Relation::enforceMorphMap()` in AppServiceProvider means `auditable_type` and `model_type` store short keys such as `client` — add new models to that map or morphs will throw. Relations that point at `Client` from schedules and deliveries use `->withTrashed()` so history stays readable after a client is archived; queries that decide whether an email may be sent therefore state `whereNull('deleted_at')` explicitly (see the `due` scope). Table search/filter/sort logic lives in one `#[Scope] filtered(Builder, XFilters)` per model so a table and its CSV export can never disagree.

## A schedule targets a client or its own recipient list
`NotificationSchedule.client_id` is nullable. With a client it sends to that client's address; without one it sends to `recipients` (a JSON list cast to `EmailAddress` value objects via `AsEmailAddressList`) under its own `name`. `target` is a derived accessor (`NotificationTarget::fromClientId`), not a column — clients are never hard-deleted, so a null client_id definitionally means "recipient list". Use `displayName()` for the label (needs the `client` relation loaded) and `recipientAddresses()` for where an occurrence actually goes; never read `client->name` or `client->email` without checking the target first. A schedule never changes target: `UpdateNotificationScheduleAction` only writes name/recipients when the schedule is already a list, and the form request reads the target off the route (schedule, then client) before the input. The `due` scope only requires an active, non-archived client for client schedules — a list schedule answers to nobody's status. `subject`/`message` belong to the Blank template alone.
