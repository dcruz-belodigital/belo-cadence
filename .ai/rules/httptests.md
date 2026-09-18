---
paths:
  - '{app/Http,tests}/**'
---

# Httptests

## Lists of schedules eager load client, and a fixture needs two rows to prove it
`NotificationSchedulePolicy::send()` reads `$schedule->client`, so any page that renders schedule actions must load that relation — `->with('client')` on the query, or `$schedule->load('client')` for one. That includes the client's own page: `ClientController::show()` eager loads `client` on its nested `notificationSchedules` load even though the parent *is* that client, because the policy runs against the schedule.

Lazy loading is prevented outside production, but Eloquent only marks models as protected when a query returned **more than one row** (`Builder::hydrate()`: `if (count($items) > 1)`). A fixture with a single schedule therefore renders happily while the real page throws `LazyLoadingViolationException`. Give a client two schedules in any test that stands in for a listing — that is how `clients.show` shipped broken past a green suite.
