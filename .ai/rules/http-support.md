---
paths:
  - 'app/{Http,Support}/**'
---

# Http Support

## Timezones: recurrence in application time, display in reader time
Timestamps are stored in UTC. Recurrence maths runs in the application timezone (`ApplicationSettings::current()->default_timezone`) so a monthly schedule keeps its intended local day and time; `CalculateNextNotificationDateAction` takes that timezone as an explicit argument and stays pure. Everything a person reads or types uses their own timezone through `App\Support\ViewerTimezone`: views render timestamps only via `<x-datetime>`, and Form Requests convert `datetime-local` input to UTC in `prepareForValidation()` using the `ConvertsViewerDateTimes` trait — convert before validating, otherwise rules such as `after:now` compare the wrong instant.
