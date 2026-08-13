# Belo Cadence — Laravel 13 Implementation Prompt

## 1. Objective

Build **Belo Cadence**, an internal Laravel application for managing recurring client email notifications.

The main problem Belo Cadence should solve is simple:

> The company must know what client communication needs to happen, when it needs to happen, whether it actually happened, and exactly what was sent and to whom.

Belo Cadence should make recurring client communication:

- Predictable
- Visible
- Auditable
- Easy to configure
- Difficult to forget
- Easy to review historically

This is a greenfield implementation inside a newly created Laravel 13 application.

The application should be production-quality, but the V1 domain must remain intentionally small.

Do not over-engineer it.

---

# 2. Product Name

Use the following naming consistently:

```text
Product name: Belo Cadence
Application name: Belo Cadence
Repository/project name: belo-cadence
Internal shorthand: Cadence
```

---

# 3. Core V1 Domain

The central business model is:

```text
Client
    +
Client Notification Schedule
    +
Client Notification Delivery
```

Static email templates support this domain, but they are application source code rather than database-managed entities.

Conceptually:

```text
Client
    has many
Client Notification Schedules

Client Notification Schedule
    produces
Client Notification Deliveries
```

A schedule answers:

> What email should this client receive, and when?

A delivery answers:

> What was actually sent, when was it sent, to whom, and was it successful?

---

# 4. Explicit V1 Non-Goals

Do NOT introduce models or modules for:

- Agreements
- Contracts
- Payments
- Invoices
- Domains
- Hosting
- Renewals
- Subscriptions
- Projects
- Tasks
- CRM pipelines

A notification may be about any of those concepts, but Belo Cadence does not need to understand those concepts yet.

For example, a static email template may be called:

```text
Annual Hosting Reminder
```

but there should not be a `HostingAgreement` model unless such a domain is explicitly introduced in the future.

Do not model hypothetical future requirements.

---

# 5. Technology Stack

Mandatory:

- Laravel 13
- PHP 8.4+
- Blade
- Alpine.js
- Tailwind CSS
- Eloquent ORM
- SQL database
- Pest
- Laravel Policies
- Spatie Laravel Permission

Livewire is allowed only when it provides a meaningful usability improvement.

Prefer Laravel first-party functionality.

Only install third-party packages when the benefit clearly outweighs the additional dependency and maintenance cost.

Do not install packages simply because they save a small amount of straightforward Laravel code.

Do not introduce:

- React
- Vue
- Inertia
- Filament
- Admin panel frameworks
- Generic CRUD frameworks
- Repository frameworks
- Generic service layers
- Domain-driven architecture frameworks

---

# 6. General Architecture Philosophy

The project structure is extremely important.

The codebase must be understandable by:

- A developer who did not build it
- A developer unfamiliar with the business
- A reviewer navigating the code for the first time
- An AI assisting with the project later

Prioritize:

1. Laravel conventions
2. Clear naming
3. Predictable locations
4. Strict typing
5. Explicit behavior
6. Small focused classes
7. Testability
8. Maintainability

Avoid:

- Clever abstractions
- Excessive indirection
- Generic metadata-driven CRUD engines
- Unnecessary patterns
- Large inheritance hierarchies
- Hidden behavior
- Premature abstractions

A small amount of obvious duplication is preferable to a generic abstraction that makes normal code difficult to follow.

The application should still look and feel like a Laravel application.

---

# 7. Suggested Folder Organization

Stay close to Laravel defaults.

Use feature grouping inside normal Laravel directories when useful.

A reasonable structure is:

```text
app/
├── Actions/
│   ├── Clients/
│   ├── ClientNotifications/
│   ├── Imports/
│   ├── Exports/
│   ├── Roles/
│   ├── Settings/
│   └── Users/
│
├── Data/
│   ├── Clients/
│   ├── ClientNotifications/
│   ├── Roles/
│   ├── Settings/
│   └── Users/
│
├── Enums/
│
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
├── Models/
│
├── Policies/
│
├── ValueObjects/
│
├── Mail/
│
└── Notifications/
```

Do not create layers such as:

```text
Domain/
Application/
Infrastructure/
Presentation/
```

unless an extremely strong concrete need exists.

Do not turn the application into a custom framework.

---

# 8. Strict PHP

Every first-party PHP file must begin with:

```php
<?php

declare(strict_types=1);
```

Use the strictest practical typing throughout.

Use:

- Parameter types
- Return types
- Typed properties
- Constructor property promotion
- `final` where extension is not intended
- `readonly` for immutable data structures
- PHPDoc generics where useful
- PHPDoc array shapes when arrays cannot reasonably be avoided

Avoid:

- `mixed`
- Untyped arrays
- Arbitrary associative arrays passed across application layers
- Dynamic properties
- Hardcoded state strings
- Weakly typed application boundaries

---

# 9. Dates and Time

Use immutable dates for domain/application logic where practical.

Prefer:

```php
CarbonImmutable
```

over mutable date instances when mutation is unnecessary.

All scheduled notification calculations must respect an explicit application/user timezone.

Store timestamps consistently in the database according to normal Laravel/database conventions.

Convert them for display using the appropriate application/user timezone.

Do not scatter timezone conversion logic throughout Blade views.

---

# 10. Value Objects

Value Objects are an important part of the architecture.

Use a Value Object when a primitive value has:

- Validation rules
- Normalization rules
- Meaningful invariants
- Domain-specific behavior required to keep it valid

A Value Object should make invalid states difficult or impossible to represent.

Good candidates may include:

```text
EmailAddress
TimezoneIdentifier
```

Additional Value Objects may be introduced where they clearly improve correctness.

A Value Object must:

- Be immutable
- Be strictly typed
- Validate itself during construction
- Represent one concept
- Have deterministic behavior
- Have no database/network/file side effects
- Be easy to unit test

Prefer:

```php
final readonly class EmailAddress
{
    public function __construct(
        public string $value,
    ) {
        // Validate invariant.
    }
}
```

Use named constructors only when they materially improve clarity.

---

# 11. Do Not Overuse Value Objects

Do not wrap every primitive merely for architectural purity.

For example, do not create classes such as:

```text
ClientId
ClientName
UserId
Notes
Description
CreatedAt
```

unless those values genuinely contain their own validation or invariants.

Use Value Objects because the domain benefits from them, not because a rule says everything must be an object.

---

# 12. Eloquent and Value Objects

When an Eloquent attribute naturally represents a Value Object, use an Eloquent custom cast when that keeps the model clean and understandable.

For example:

```text
Client.email -> EmailAddress
User.email -> EmailAddress
```

Do not introduce complicated persistence mapping purely to support Value Objects.

Clarity remains the priority.

---

# 13. Enums

Use PHP backed Enums for finite sets of known values.

Examples:

```text
ClientStatus
ClientNotificationFrequency
ClientNotificationDeliveryStatus
ClientEmailTemplate
ThemePreference
AuditAction
PermissionName
```

Never scatter hardcoded strings for:

- Statuses
- Types
- Frequencies
- Themes
- Template identifiers
- Delivery states
- Permission identifiers

Persist stable machine-readable enum values.

Example:

```php
enum ClientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
```

User-facing enum labels must come from translation files.

Do not use database enum column types unless there is a compelling reason.

Prefer normal string columns cast to PHP Enums.

---

# 14. DTOs

Whenever validated application data crosses layers, use immutable DTOs.

Store DTOs under:

```text
app/Data/
```

DTOs must:

- Be immutable
- Prefer `final readonly`
- Contain data only
- Contain no persistence logic
- Contain no business operations
- Be strictly typed
- Use Enums and Value Objects rather than weak primitives where appropriate

Example:

```php
final readonly class CreateClientData
{
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public ClientStatus $status,
        public ?string $notes,
    ) {
    }
}
```

---

# 15. Form Requests

Every normal HTTP endpoint receiving mutable user data must use a dedicated Form Request.

Examples:

```text
StoreClientRequest
UpdateClientRequest

StoreClientNotificationScheduleRequest
UpdateClientNotificationScheduleRequest

StoreUserRequest
UpdateUserRequest

StoreRoleRequest
UpdateRoleRequest

UpdateProfileRequest
UpdatePreferencesRequest
UpdateApplicationSettingsRequest

ImportClientsRequest
```

Never perform request validation directly inside controllers.

A Form Request may expose a typed conversion method:

```php
public function toData(): CreateClientData
```

Use this pattern consistently when appropriate.

---

# 16. Controllers

Controllers must remain thin.

Controllers may:

- Authorize
- Accept Form Requests
- Convert validated requests to DTOs
- Call Actions
- Execute straightforward read queries
- Return Blade views
- Redirect
- Return downloads

Controllers must not contain business logic.

Simple query logic does not need an Action.

For example, this is acceptable:

```php
Client::query()
    ->latest()
    ->paginate();
```

Do not create Actions merely to hide simple Eloquent reads.

---

# 17. Actions

All meaningful business operations and state mutations must use the Action Pattern.

Examples:

```text
CreateClientAction
UpdateClientAction
ArchiveClientAction

CreateClientNotificationScheduleAction
UpdateClientNotificationScheduleAction
DisableClientNotificationScheduleAction

ProcessDueClientNotificationsAction
SendClientNotificationAction
CalculateNextNotificationDateAction

CreateUserAction
UpdateUserAction

CreateRoleAction
UpdateRoleAction

UpdateApplicationSettingsAction

ImportClientsAction
ExportClientsAction

RecordAuditAction
```

Every Action must:

- Be `final`
- Be invokable
- Expose only one public method: `__invoke()`
- Have one clear responsibility
- Keep implementation helpers private

Example:

```php
final class CreateClientAction
{
    public function __invoke(CreateClientData $data): Client
    {
    }
}
```

Private methods are allowed.

Do not create generic service classes.

Do not create repositories.

---

# 18. Pure Helper Classes

Not every technical class needs to be an Action.

Pure stateless technical helpers are acceptable where they genuinely improve the design.

Examples may include:

```text
CsvWriter
CsvReader
```

Do not create a generic `ClientService`, `NotificationService`, or similar layer.

Business operations belong in Actions.

---

# 19. No Hidden Business Side Effects

Avoid:

- Eloquent observers
- Model boot hooks containing business behavior
- Event/listener chains for ordinary application workflows
- Hidden global side effects

Business behavior must be explicit.

For example:

```text
CreateClientAction
    creates client
    optionally applies selected default notification schedules
    explicitly records audit information
```

Do not make a `ClientObserver` silently perform those operations.

Laravel framework events may still be used where the framework naturally relies on them.

---

# 20. Authentication

Belo Cadence is an internal authenticated application.

There is no public application.

There must be:

- No marketing homepage
- No public dashboard
- No public client pages
- No public registration

Guests should only be able to reach authentication pages required to access the system.

At minimum:

```text
Login
```

Password reset/recovery may also exist if appropriate.

Visiting:

```text
/
```

should:

- Redirect authenticated users to the dashboard
- Redirect guests to login

Users can only be created by authorized users inside Belo Cadence.

---

# 21. Users

Use Laravel's standard User model.

Initial user functionality should include:

- Name
- Email
- Password
- Active/inactive status
- Roles
- Preferences
- Internal application notifications
- Audit references

Inactive users cannot authenticate.

Do not destroy historical records merely because a user becomes inactive.

---

# 22. Roles and Permissions

Use:

```text
spatie/laravel-permission
```

Roles represent bundles of permissions.

Application code should authorize based primarily on permissions, not role names.

Avoid code such as:

```php
$user->hasRole('Admin')
```

throughout the application.

Instead use Policies and permission checks.

A user may have multiple roles.

Do not expose arbitrary direct permission assignment to users in V1 unless required.

Permissions themselves are defined in code.

Roles are manageable in the UI.

---

# 23. Permission Naming Convention

Use a single predictable convention.

For example:

```text
dashboard.view

clients.viewAny
clients.view
clients.create
clients.update
clients.delete
clients.import
clients.export

client-notifications.viewAny
client-notifications.view
client-notifications.create
client-notifications.update
client-notifications.delete
client-notifications.export

notification-deliveries.viewAny
notification-deliveries.view
notification-deliveries.export

users.viewAny
users.view
users.create
users.update
users.delete
users.import
users.export

roles.viewAny
roles.view
roles.create
roles.update
roles.delete

audit-log.viewAny
audit-log.view
audit-log.export

application-settings.view
application-settings.update

default-client-notifications.view
default-client-notifications.update
```

Adjust exact naming if Laravel/Spatie conventions suggest something cleaner, but remain consistent.

---

# 24. Import and Export Permissions

Import and export are explicit capabilities.

Do not automatically assume:

```text
viewAny = export
create = import
```

Instead use explicit permissions:

```text
clients.export
clients.import
```

An import must also respect the permissions of the underlying mutations it performs.

For example, a user with:

```text
clients.import
```

but without permission to create clients must not be allowed to use an import to bypass client creation authorization.

---

# 25. Policies

Create Laravel Policies for protected application resources.

Use policies for normal resource authorization.

Examples:

```text
ClientPolicy
ClientNotificationSchedulePolicy
ClientNotificationDeliveryPolicy
UserPolicy
RolePolicy
AuditPolicy
```

Avoid gate closures for application business authorization.

Authorization must exist on the backend.

Hiding a button in Blade is not sufficient security.

---

# 26. Navigation Authorization

Navigation must respect permissions.

If a user cannot access Clients, the Clients navigation item should not appear.

The same applies to:

- Users
- Roles
- Audit log
- Settings
- Imports
- Exports
- Cadence management

However, backend authorization remains mandatory even when the navigation item is hidden.

---

# 27. Initial Administrator

Create an initial administrator setup through essential seeders.

The administrator role should initially contain every application permission.

Administrator credentials must not be hardcoded as a real production credential.

Use environment/configuration values appropriately.

Make it difficult to accidentally leave the application with no user capable of administering access.

Do not allow the final viable administrator to accidentally remove their own essential access without protection.

---

# 28. Client Model

Keep Client intentionally small in V1.

Suggested schema:

```text
clients

id
name
email
status
notes nullable
created_at
updated_at
deleted_at nullable
```

Possible casts:

```text
email -> EmailAddress
status -> ClientStatus
```

Use soft deletion if it provides the cleanest lifecycle.

At minimum:

```php
enum ClientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
```

Inactive or deleted clients must never receive automatic client emails.

---

# 29. Client Relationships

A Client has many:

```text
ClientNotificationSchedules
ClientNotificationDeliveries
```

The delivery relationship may be direct as well as accessible through schedules if that makes historical queries significantly clearer.

Do not create additional client-related models until they are needed.

In particular, do not create client contacts in V1 unless implementation requirements make them unavoidable.

---

# 30. Static Client Email Templates

This requirement is strict:

**Email templates are not database-managed.**

There must be no:

- `email_templates` table
- Template CRUD
- WYSIWYG editor
- HTML editor
- Template management page
- Dynamic variable builder
- User-editable email body

Email templates are part of the application source code.

---

# 31. Client Email Template Enum

Declare the available templates explicitly in code.

For example:

```php
enum ClientEmailTemplate: string
{
    case GeneralReminder = 'general_reminder';
    case MonthlyReminder = 'monthly_reminder';
    case AnnualReminder = 'annual_reminder';
}
```

Each available template should have a predictable association with:

- A translated label
- A Blade view
- A subject or subject translation key

The exact implementation may use enum methods or another small explicit source-controlled mapping.

Do not create a configurable registry in the database.

---

# 32. Email Blade Views

Store client email templates in a predictable location such as:

```text
resources/views/mail/client-notifications/
```

Example:

```text
general-reminder.blade.php
monthly-reminder.blade.php
annual-reminder.blade.php
```

These should be simple Blade email views.

Business logic must not live inside the Blade email.

The view should receive already-prepared data.

Adding a new available client email template should be an intentional developer task.

Ideally it should require only:

1. Add the enum/catalog entry
2. Create the Blade view
3. Add translation text
4. Add/update tests

That is all.

---

# 33. Creating a Client Notification

When an authorized user creates a notification schedule for a client, they should:

1. Select one of the available static email templates
2. Select the frequency
3. Select the first scheduled date/time
4. Enable or disable the schedule

Keep the form simple.

Do not create a dynamic workflow builder.

---

# 34. Client Notification Schedule

Create a model representing recurring client email configuration.

Suggested name:

```text
ClientNotificationSchedule
```

Suggested fields:

```text
id
client_id
template
frequency
starts_at
next_send_at
last_sent_at nullable
is_enabled
created_at
updated_at
deleted_at nullable if justified
```

Adjust exact naming if a clearer schema emerges.

Use explicit casts.

For example:

```text
template -> ClientEmailTemplate
frequency -> ClientNotificationFrequency
starts_at -> immutable_datetime
next_send_at -> immutable_datetime
last_sent_at -> immutable_datetime
is_enabled -> boolean
```

---

# 35. Notification Frequencies

Keep V1 deliberately small.

Support:

```text
one_time
monthly
yearly
```

Use:

```php
enum ClientNotificationFrequency: string
```

Do not implement:

- Cron expressions
- RRULE editors
- Arbitrary custom recurrence expressions
- Complex calendar builders
- Recurrence DSLs

The architecture should make another enum case reasonably easy to introduce later.

---

# 36. Recurrence Anchor

`starts_at` represents the schedule's recurrence anchor.

Example:

```text
starts_at: 2026-09-15 09:00
frequency: yearly
```

means the notification should recur yearly based on September 15 at the configured time.

Store the calculated:

```text
next_send_at
```

so upcoming notifications are easy and efficient to query.

Do not calculate every future notification occurrence dynamically on every page request.

---

# 37. Monthly Recurrence Rule

Monthly recurrence must have deterministic behavior.

If a notification originates on a day that does not exist in a target month, use the last valid day of that month.

Example:

```text
January 31
February -> February 28 or 29
March -> March 31
April -> April 30
```

The recurrence must remain anchored to the original intended day rather than permanently becoming the 28th after February.

Test this explicitly.

---

# 38. Yearly Leap-Year Rule

Yearly recurrence must also be deterministic.

For a schedule anchored on February 29:

```text
Leap year -> February 29
Non-leap year -> February 28
```

When the next leap year arrives, return to February 29.

Test this explicitly.

---

# 39. One-Time Notifications

A one-time notification should have exactly one scheduled occurrence.

After successful processing:

```text
next_send_at = null
```

and the schedule should no longer be considered active/due.

Choose a clear implementation, such as automatically disabling the schedule after successful one-time delivery.

Keep the resulting state understandable in the UI.

---

# 40. Notification Eligibility

A notification may only be automatically processed when:

- The schedule is enabled
- The schedule has a `next_send_at`
- `next_send_at` is due
- The client exists
- The client is active
- The client is not deleted
- The recipient email is valid

Do not send automatic emails for inactive/deleted clients.

---

# 41. Client Notification Recipient

For V1, keep recipients simple.

Use the client's email address as the destination for scheduled client notifications.

Do not introduce:

- Client contact management
- Recipient groups
- CC configuration
- BCC configuration
- Multiple recipients per schedule

unless required by an already-existing project constraint.

The exact recipient used must always be copied into the historical delivery record.

This makes adding contact/recipient functionality later possible without compromising V1 delivery history.

---

# 42. Client Notification Delivery

This model is critical.

Create:

```text
ClientNotificationDelivery
```

This represents an actual email send attempt/history entry.

It must permanently answer:

- Which client?
- Which schedule?
- Which template?
- Which email address?
- Which recipient name?
- What subject?
- What exact email content?
- Which scheduled occurrence?
- When was sending attempted?
- When was it successfully sent?
- Did it succeed?
- If it failed, why?

---

# 43. Suggested Delivery Schema

A reasonable structure:

```text
client_notification_deliveries

id
client_notification_schedule_id
client_id
template
recipient_email
recipient_name nullable
sender_email
sender_name
subject
body_html
scheduled_for
attempted_at
sent_at nullable
status
failure_message nullable
created_at
```

Historical deliveries do not need normal `updated_at` semantics if they are designed as immutable records, but use normal Laravel timestamps if doing so keeps the implementation clearer.

Choose deliberately.

---

# 44. Delivery Status

Use a PHP Enum.

For example:

```text
pending
sent
failed
```

Do not store arbitrary status strings.

---

# 45. Delivery Snapshot

A delivery must snapshot the actual outgoing message.

Store:

```text
recipient
sender
subject
rendered HTML body
template identifier
scheduled occurrence
```

This information must never be recomputed when viewing history.

If tomorrow:

- The client's email changes
- The client's name changes
- The template Blade file changes
- The company sender changes

an old delivery must still show exactly what existed when that delivery was produced.

Historical truth is more important than avoiding a duplicated HTML snapshot.

---

# 46. Delivery Immutability

Delivery records are historical evidence.

Normal users must not be able to edit them.

Do not create:

```text
Edit Delivery
Update Delivery
Import Deliveries
```

Delivery history may be:

- Viewed
- Filtered
- Searched
- Exported

but should not behave like mutable CRUD data.

---

# 47. Prevent Duplicate Sends

Duplicate protection is mandatory.

The same scheduled occurrence must not accidentally be sent twice because:

- The scheduler overlaps
- The command is executed manually while scheduled execution is running
- Two workers/processes reach the same schedule
- A transaction retries

Use appropriate safeguards such as:

- Database transaction
- Row-level locking where appropriate
- Scheduler `withoutOverlapping()`
- A database uniqueness strategy for a scheduled occurrence

A useful invariant is:

```text
One normal delivery occurrence per
(schedule_id + scheduled_for)
```

Design the database around making duplicate sends difficult or impossible.

Do not rely only on an in-memory check.

---

# 48. Failed Delivery Behavior

A failed send must:

1. Create/preserve its delivery history
2. Be clearly marked as failed
3. Store a useful failure message
4. Appear in operational UI
5. Trigger an appropriate internal application notification

Do not silently swallow mail exceptions.

Do not allow failure of one email to destroy historical information about the attempt.

Keep automatic retry behavior simple in V1.

Do not introduce a complicated retry engine.

If automatic retries are implemented using first-party Laravel facilities, the resulting history and duplicate protection must remain correct and understandable.

---

# 49. Email Sending

Use Laravel Mail.

Do not implement direct SMTP clients.

Create a clear source-controlled Mailable or equivalent mail class responsible for rendering a static selected template.

Business orchestration belongs in Actions.

Conceptually:

```text
ProcessDueClientNotificationsAction
    finds due schedules

SendClientNotificationAction
    prepares delivery snapshot
    sends Laravel Mail
    records result
    advances schedule
```

Keep responsibilities clear.

---

# 50. Email Sender

Email sender information should come from application settings/configuration where appropriate.

Support:

```text
sender name
sender email
```

Do not expose infrastructure credentials through application settings.

Credentials such as SMTP passwords remain environment configuration.

Snapshot the sender used into each delivery.

---

# 51. Laravel Scheduler

Use Laravel's scheduler for processing due client notifications.

Create an explicit command/action flow.

For example:

```text
php artisan cadence:process-notifications
```

The exact command name may differ if a clearer convention exists.

Schedule it using Laravel's normal scheduler configuration.

Prevent overlapping executions.

The command itself should remain thin and delegate business logic to an Action.

---

# 52. Upcoming Notifications

An upcoming-notifications overview is a core feature.

Create a dedicated page for upcoming scheduled client emails.

It should make it immediately obvious what communication is coming next.

Show fields such as:

```text
Client
Template
Recipient
Frequency
Next send date/time
Status
```

Order upcoming notifications by nearest date first by default.

---

# 53. Upcoming Notification Filters

Provide practical filters.

At minimum:

- Client search
- Template
- Frequency
- Enabled/disabled
- Time range

Useful predefined time ranges may include:

```text
Today
Next 7 days
Next 30 days
```

Do not build an unnecessarily complex query builder.

---

# 54. Delivery History

Create a dedicated delivery-history page.

Support:

- Search
- Client filter
- Template filter
- Delivery status
- Date range
- Pagination
- Export

Allow users with permission to open an individual delivery and review:

- Client
- Recipient
- Sender
- Subject
- Rendered content
- Scheduled time
- Attempt time
- Sent time
- Status
- Failure details

Do not allow edits.

---

# 55. Dashboard

Create a useful operational dashboard.

The dashboard should answer:

> What needs attention?

Suggested statistics:

```text
Notifications due today
Notifications due in the next 7 days
Successful deliveries this month
Failed deliveries
```

Also show:

- Next upcoming notifications
- Recent failed deliveries
- Recent delivery activity if useful

Do not add meaningless charts simply to fill the dashboard.

Use charts only if they genuinely improve understanding.

---

# 56. Internal User Notifications

Client email notifications and application user notifications are separate systems.

Use Laravel's built-in notification infrastructure for notifications to Belo Cadence users.

Use the database notification channel.

Examples of useful internal notifications:

```text
Client email delivery failed
Important import failed
```

Do not send excessive internal notifications for routine successful operations.

---

# 57. Internal Notification UI

Provide:

- Notification bell
- Unread count
- Dropdown/popover of recent notifications
- Mark as read
- Mark all as read
- Full notification page if useful

Use Alpine.js or Livewire depending on which produces the cleanest implementation.

Do not put core domain logic inside the notification UI component.

---

# 58. User Preferences

Each user should have personal preferences.

Initial preferences:

```text
theme
locale
timezone
```

Theme options:

```text
system
light
dark
```

Locale defaults to English.

Use typed fields rather than an unrestricted JSON blob when practical.

Do not turn a JSON preferences field into an unstructured dumping ground.

---

# 59. Application Settings

Provide application-level configuration for authorized users.

Initial settings may include:

```text
Application/company display name
Default locale
Default timezone
Client email sender name
Client email sender address
```

Settings should be explicit and typed.

Avoid one giant arbitrary JSON configuration object.

Infrastructure secrets remain in `.env`.

Application setting changes must be audited.

---

# 60. Default Notification Setup for New Clients

The application should support defining which notification configurations are commonly applied to newly created clients.

Keep this intentionally simple.

Authorized users should be able to configure default entries consisting of:

```text
Static email template
Frequency
Enabled by default
```

Do not create a generic preset engine.

Do not create multiple complex client-template profiles in V1.

---

# 61. Applying Defaults to a New Client

When creating a client:

1. Create the client
2. Show/offer the configured default notification entries
3. Allow the user to apply or skip them
4. Require the first scheduled date/time for schedules that are being created
5. Create normal `ClientNotificationSchedule` records

Do not invent scheduled dates automatically when the business has not provided enough information.

A user should understand exactly what schedules will be created.

---

# 62. Defaults Are Copies, Not Live Inheritance

Once a default notification setup has been applied to a client, the resulting schedules belong to that client.

Changing global defaults later must NOT silently modify existing client schedules.

Default configuration affects future setup only.

This avoids hidden behavior.

---

# 63. User Management

Authorized users must be able to:

- List users
- Search users
- View user
- Create user
- Edit user
- Activate user
- Deactivate user
- Assign roles

There is no public registration.

Passwords must use Laravel's normal password hashing and validation.

Never expose hashes.

---

# 64. Role Management

Authorized users should be able to:

- List roles
- View roles
- Create roles
- Rename/edit roles
- Assign permissions to roles
- See users assigned to a role
- Delete roles when safe

Permissions remain source-controlled.

Do not allow users to create arbitrary new permission identifiers from the UI.

---

# 65. Profile

Authenticated users should have a profile area.

Support appropriate self-service functionality such as:

- Name
- Password
- Preferences

Changing another user's roles/status remains an administrative operation.

---

# 66. CSV Only for V1

Import/export support is CSV only.

Do not install:

- Laravel Excel
- PhpSpreadsheet
- XLSX libraries

unless another requirement makes them genuinely necessary.

CSV is sufficient for V1.

Design the architecture so another format could be introduced later without rewriting every resource.

Do not implement future formats now.

---

# 67. Exportability

Every normal user-facing resource table should be exportable when the user has the corresponding export permission.

Examples:

```text
Clients
Users
Notification schedules
Delivery history
Audit history
Roles where useful
```

Exports must respect authorization.

---

# 68. Importability

Every mutable resource table where importing records makes semantic sense should be importable.

Examples:

```text
Clients
Users
Possibly notification schedules
```

Read-only historical/system datasets must NOT be importable.

Examples:

```text
Client notification deliveries
Audit log
Laravel internal notifications
```

Importing historical truth would corrupt the application's auditability.

This is an intentional exception to the general import/export rule.

---

# 69. Raw CSV Export

Support a raw export mode.

Raw export is machine-oriented and represents the underlying application data.

Use:

- Stable column names
- Raw enum values
- ISO-compatible date/time values
- IDs/foreign keys where appropriate

Example:

```csv
id,name,email,status,created_at,updated_at
```

Raw exports should be predictable and suitable for technical processing.

---

# 70. Table CSV Export

Support a human-oriented table export where appropriate.

This export should reflect the current table representation.

It should respect where applicable:

- Search
- Filters
- Sorting
- Currently relevant visible columns

Example table export:

```csv
Name,Email,Status,Created
```

Keep raw export and table export as clearly different concepts.

---

# 71. Import Templates

Every importable resource must provide:

```text
Download import template
```

The template must show the required raw CSV structure.

Example:

```csv
name,email,status
Example Client,client@example.com,active
```

Use machine-readable stable column names.

---

# 72. CSV Import Workflow

Use a safe workflow:

```text
Download Template
        ↓
Prepare CSV
        ↓
Upload
        ↓
Validate File
        ↓
Validate Rows
        ↓
Show Errors / Summary
        ↓
Import
```

Do not silently partially import a file without clearly communicating what happened.

---

# 73. Atomic Imports

Prefer atomic imports for V1.

If one row fails validation:

```text
do not persist half of the file
```

Show understandable validation errors instead.

If future scale requires partial/import-job behavior, it can be added later.

---

# 74. Import Validation

Imports must use the same domain rules as normal application operations.

Do not create a second weaker validation system.

Imports must respect:

- Enums
- Value Objects
- Unique constraints
- Business invariants
- Authorization
- Actions
- Audit logging

Where practical, route imported records through the same Actions used by the UI.

---

# 75. Import Updates

Do not silently guess whether an imported row creates or updates a record.

If updating existing data through import is supported, use an explicit stable identifier.

The intended behavior must be clear to the user.

Avoid matching records by approximate values.

---

# 76. Tables

Tables throughout Belo Cadence should have a consistent UX.

Where appropriate support:

- Search
- Sorting
- Filters
- Pagination
- Empty state
- Row actions
- Bulk selection only when actually useful
- Import
- Export

Do not build a giant generic table framework.

Create reusable visual primitives, while leaving each resource table implementation readable.

A developer opening the Clients implementation should immediately understand:

- Columns
- Filters
- Actions
- Query

without tracing through a complex metadata system.

---

# 77. Livewire

Livewire is a tool, not the application's architecture.

Default to:

```text
Blade
+
normal Laravel requests
+
Alpine.js
```

Use Livewire only when server-driven reactivity clearly improves usability.

Good possible uses:

- Server-filtered tables
- Search without full page reload
- Notification dropdown state
- Interactive filtering

Prefer normal POST/PUT/PATCH/DELETE forms for important mutations so the Form Request + DTO + Action flow remains clear.

Do not put business logic inside Livewire components.

A Livewire component must delegate business operations to Actions.

Do not convert every page into Livewire.

---

# 78. Auditability

Important actions must be auditable.

Implement a simple first-party audit system unless a package provides an overwhelming benefit.

Do not install a large activity logging dependency without a clear reason.

Create an audit model/table.

Suggested information:

```text
id
user_id nullable
action
auditable_type
auditable_id nullable
old_values nullable
new_values nullable
metadata nullable
created_at
```

Use typed casts appropriately.

---

# 79. Audit Actors

An audit entry may have:

```text
Authenticated user
System/null actor
```

Scheduled system operations must be identifiable as system actions.

Do not attribute system-generated activity to an administrator who did not perform it.

---

# 80. What Should Be Audited

Audit important state-changing operations such as:

- Client created
- Client updated
- Client archived/deleted
- Notification schedule created
- Notification schedule updated
- Notification schedule enabled/disabled
- User created
- User updated
- User activated/deactivated
- Role created
- Role permissions changed
- Application settings changed
- Default notification configuration changed
- Import performed
- Important export performed when useful
- Other security-sensitive administration

Client notification delivery history already provides detailed sending history and does not need redundant generic audit noise for every stored field.

Do not audit ordinary page views.

---

# 81. Audit Immutability

Audit history must be read-only from the UI.

Allow:

```text
view
filter
search
export
```

Do not allow:

```text
create manually
edit
delete
import
```

---

# 82. Deletion Strategy

Preserve historical integrity.

Important records should not disappear merely because a related resource is removed.

For example:

```text
Deleting/archive Client
    must not delete historical deliveries

Deleting Notification Schedule
    must not delete historical deliveries

Deactivating User
    must not delete audit history
```

Prefer soft deletion or inactive states for important operational resources.

Avoid permanent deletion from normal UI where it would destroy useful history.

---

# 83. Eloquent Model Casts

Every castable first-party model attribute must have an explicit cast.

Use Laravel's current recommended model cast style.

Examples:

```text
Enums
Booleans
Immutable datetimes
Arrays/JSON where genuinely needed
Value Object casts
```

Do not rely on implicit interpretation.

---

# 84. Model PHPDoc

Every first-party Eloquent model must contain complete PHPDoc.

Document:

- Attributes
- Nullable attributes
- Enums
- Value Objects
- Dates
- Relationships
- Relationship collection types

Example:

```php
/**
 * @property int $id
 * @property string $name
 * @property EmailAddress $email
 * @property ClientStatus $status
 * @property string|null $notes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 *
 * @property-read Collection<int, ClientNotificationSchedule> $notificationSchedules
 */
final class Client extends Model
{
}
```

Keep PHPDoc accurate when migrations/models change.

---

# 85. Factories

Every first-party application model that reasonably supports factories must have one.

Factories must generate valid realistic data.

Use factory states to make tests expressive.

Examples:

```php
Client::factory()->active()
Client::factory()->inactive()

ClientNotificationSchedule::factory()->oneTime()
ClientNotificationSchedule::factory()->monthly()
ClientNotificationSchedule::factory()->yearly()
ClientNotificationSchedule::factory()->disabled()
ClientNotificationSchedule::factory()->due()

ClientNotificationDelivery::factory()->sent()
ClientNotificationDelivery::factory()->failed()
```

Do not create factory states with ambiguous meaning.

---

# 86. Seeder Philosophy

There are two fundamentally different types of data:

```text
Required system data
Demo/testing data
```

Never mix them.

---

# 87. DatabaseSeeder

`DatabaseSeeder` must create only data required to establish a usable new environment.

It may create:

- Application permissions
- Essential roles
- Initial administrator
- Required application setting records
- Other truly mandatory system records

It must NOT create:

- Fake clients
- Fake notification schedules
- Fake deliveries
- Demo users
- Fake audit history
- Fake user notifications

Running:

```bash
php artisan db:seed
```

must be safe for a real new environment.

---

# 88. DemoSeeder

Create:

```text
DemoSeeder
```

It runs only explicitly:

```bash
php artisan db:seed --class=DemoSeeder
```

`DatabaseSeeder` must never call `DemoSeeder`.

---

# 89. DemoSeeder Completeness

The DemoSeeder should make it possible to test essentially every visible state in the application.

Create realistic examples of:

- Multiple users
- Multiple roles
- Different permission levels
- Active clients
- Inactive clients
- Clients with notes
- Clients without notes
- One-time schedules
- Monthly schedules
- Yearly schedules
- Enabled schedules
- Disabled schedules
- Notifications due today
- Notifications due soon
- Notifications later in the future
- Previously sent notifications
- Failed deliveries
- Different static email templates
- Read internal notifications
- Unread internal notifications
- Different themes/preferences
- Different timezones if useful
- Audit entries
- Default client notification configurations

Every meaningful badge, status, empty/non-empty state, scheduling state and history state should be easy to test using demo data.

Use clearly fictional data.

---

# 90. Dedicated Demo Seeders

Where it improves organization, create dedicated seeders such as:

```text
DemoUserSeeder
DemoClientSeeder
DemoClientNotificationSeeder
DemoAuditSeeder
```

and let:

```text
DemoSeeder
```

orchestrate them.

Keep dependencies/order understandable.

---

# 91. Internationalization

The entire application is English in V1.

However, it must be translation-ready from the beginning.

Do not hardcode user-facing application text throughout Blade views.

Use Laravel translation files.

A reasonable organization:

```text
lang/en/
├── common.php
├── navigation.php
├── dashboard.php
├── clients.php
├── cadence.php
├── users.php
├── roles.php
├── settings.php
└── audit.php
```

Use translations for:

- Navigation
- Page headings
- Buttons
- Form labels
- Empty states
- Status labels
- Enum labels
- Flash messages
- Modal text
- Table headings
- Email subject/copy where appropriate

English remains the canonical locale.

---

# 92. Design Goal

Belo Cadence needs a complete reusable design system.

The UI should feel:

- Minimal
- Practical
- Modern
- Professional
- Clean
- Calm
- Slightly sophisticated
- Pleasant to interact with

Do not make it flashy.

Do not copy Factorial or any other product.

Use common SaaS usability patterns.

The most important visual requirement is:

> Everything must feel like it belongs to the same system.

---

# 93. Design Tokens

Do not scatter visual decisions throughout Blade files.

Centralize the design system using semantic design tokens.

Use CSS variables integrated with the Tailwind version already present in the project.

Do not replace the project's current Tailwind approach unnecessarily.

Create semantic variables for concepts such as:

```text
background
surface
surface-elevated

foreground
foreground-muted

border
border-strong

primary
primary-hover
primary-foreground

success
warning
danger
info
```

Do not make components depend on arbitrary hardcoded colors such as:

```text
blue-600
zinc-950
gray-200
```

everywhere.

Components should consume semantic design decisions.

---

# 94. Typography Tokens

Typography must also be centralized.

Define consistent rules for:

- Font family
- Page title
- Section title
- Body
- Small/meta text
- Font weight
- Line height

Changing the main application font later should require changing the design system rather than editing dozens of files.

---

# 95. Other UI Tokens

Centralize:

- Border radius
- Shadows
- Focus rings
- Transition duration
- Form control heights
- Standard page spacing
- Content width where appropriate

Do not create dozens of arbitrary variants.

Use a small coherent system.

---

# 96. Light and Dark Mode

Support:

```text
system
light
dark
```

Theme choice belongs to the user's preferences.

Every major component must work intentionally in both modes.

Avoid a visible flash of the wrong theme during page load.

Implement initial-theme loading early enough in the document lifecycle to prevent this.

Dark mode must not be implemented as an afterthought.

---

# 97. Blade Components

Use reusable Blade components to keep views clean.

Potential components include:

```text
x-app-layout
x-page-header
x-card

x-button
x-icon-button

x-form.field
x-form.label
x-form.input
x-form.textarea
x-form.select
x-form.checkbox
x-form.error

x-badge
x-alert
x-empty-state

x-modal
x-dropdown

x-table
x-table.header
x-table.row
x-table.cell

x-pagination
```

Choose names that fit the project naturally.

Do not blindly create every example above if it does not provide value.

---

# 98. Component Philosophy

Create a reusable component when it:

- Establishes a visual standard
- Encapsulates meaningful repeated markup
- Makes consuming views cleaner
- Provides a consistent API

Do not componentize trivial one-off markup.

A component must reduce complexity, not merely move HTML to another file.

---

# 99. Component Consistency

Equivalent UI elements must always behave and look the same.

For example:

```text
Primary Button
```

must have the same:

- Height
- Radius
- Typography
- Hover state
- Focus state
- Disabled state
- Loading state

everywhere.

Apply the same principle to:

- Inputs
- Selects
- Badges
- Modals
- Tables
- Filters
- Empty states
- Page headers
- Destructive confirmation dialogs

Consistency is more important than novelty.

---

# 100. Layout

Create a professional authenticated SaaS layout.

Desktop:

```text
Sidebar
+
Top bar
+
Main content
```

Mobile:

```text
Top bar
+
Drawer navigation
+
Main content
```

Include:

- Application identity
- Navigation
- Current user
- Notification bell
- Theme control/user preference access
- Profile menu

---

# 101. Suggested Navigation

A reasonable initial information architecture:

```text
Dashboard

Clients

Cadence
    Upcoming
    Schedules
    Delivery History

Administration
    Users
    Roles
    Audit Log
    Settings

Profile
```

Adjust if a flatter structure produces better usability.

Navigation visibility must respect permissions.

---

# 102. Interaction Design

Use subtle interaction to make the application feel polished.

Good examples:

- Short transitions
- Clear hover feedback
- Strong focus states
- Submit loading state
- Disabled form state
- Confirmation before destructive actions
- Useful tooltips only where necessary
- Helpful empty states
- Good validation messages
- Toast/flash feedback

Avoid:

- Excessive animation
- Decorative gradients everywhere
- Glassmorphism everywhere
- Large motion effects
- Fancy controls that are harder to use than native patterns

---

# 103. Accessibility

Use:

- Semantic HTML
- Proper labels
- Keyboard-accessible controls
- Visible focus indicators
- Sufficient contrast
- Appropriate ARIA where necessary
- Accessible dialog behavior
- Accessible dropdown behavior

Do not rely solely on color to communicate status.

---

# 104. Empty States

Every significant list/table should have an intentional empty state.

Examples:

```text
No clients yet
No upcoming notifications
No deliveries yet
No failed deliveries
No audit records matching these filters
```

Where authorized and useful, include a relevant call to action.

Do not leave blank tables.

---

# 105. Loading and Feedback

Interactive operations should provide appropriate feedback.

Examples:

- Disabled submit while submitting
- Loading indicator where necessary
- Success flash
- Failure flash
- Clear CSV validation result
- Clear email failure state

Do not add artificial loading effects when operations are already instant.

---

# 106. Testing Framework

Use Pest.

Create:

- Unit tests
- Feature tests

Use Laravel testing utilities and fakes.

Test behavior rather than implementation details.

---

# 107. Mandatory Authentication Tests

Test:

- Guests cannot access application routes
- Registration route/page does not exist
- Active user can authenticate
- Inactive user cannot authenticate
- Authenticated root redirects correctly
- Guest root redirects to login

---

# 108. Mandatory Authorization Tests

Test:

- Policies
- Permission-protected routes
- Hidden unauthorized actions
- Import permission
- Export permission
- Administration access
- Settings access
- Delivery history access
- Audit log access

Backend authorization is the source of truth.

---

# 109. Mandatory Value Object Tests

Test every Value Object's invariants.

For example:

```text
Valid email accepted
Invalid email rejected
Email normalization if applicable
Valid timezone accepted
Invalid timezone rejected
```

Do not only test Value Objects indirectly through feature tests.

---

# 110. Mandatory Client Tests

Test:

- Create
- Update
- Inactivate
- Reactivate if supported
- Archive/delete
- Validation
- Authorization
- Soft-delete/history behavior
- Invalid email handling

---

# 111. Mandatory Schedule Tests

Test:

- Create one-time schedule
- Create monthly schedule
- Create yearly schedule
- Update schedule
- Disable schedule
- Authorization
- Initial next date
- Monthly recurrence
- Yearly recurrence
- 31st-day behavior
- February behavior
- February 29 behavior
- Disabled schedules ignored
- Inactive clients ignored
- Deleted clients ignored

---

# 112. Mandatory Sending Tests

Use Laravel Mail fakes where appropriate.

Test:

- Due notification is processed
- Future notification is not processed
- Correct Blade template selected
- Correct recipient selected
- Correct sender selected
- Correct subject rendered
- Delivery record created
- Rendered body snapshotted
- Recipient snapshotted
- Sender snapshotted
- Successful state recorded
- Failed state recorded
- Failure message stored
- Next occurrence calculated
- One-time schedule completed correctly

---

# 113. Duplicate-Send Tests

Explicitly test duplicate protection.

Simulate processing the same scheduled occurrence more than once.

The application must not send duplicate normal emails for the same occurrence.

This is a mandatory invariant.

---

# 114. Historical Integrity Tests

Test that changing:

- Client name
- Client email
- Blade template
- Application sender settings

does not mutate existing delivery history.

Historical snapshots must remain unchanged.

---

# 115. Upcoming Notification Tests

Test:

- Correct schedules appear
- Ordering
- Date-range filter
- Template filter
- Frequency filter
- Search
- Disabled behavior
- Authorization

---

# 116. CSV Export Tests

Test:

- Raw export headers
- Raw enum values
- Date formatting
- Table export
- Current filters
- Search
- Authorization
- Unauthorized access rejected

---

# 117. CSV Import Tests

Test:

- Valid file
- Invalid CSV
- Missing header
- Unexpected header behavior
- Invalid status
- Invalid email
- Duplicate unique values
- Atomic rollback
- Authorization
- Business Actions used correctly
- Audit entry produced

---

# 118. Role Tests

Test:

- Create role
- Update role
- Assign permissions
- Remove permissions
- Assign role to user
- Authorization
- Protection against losing essential administration

---

# 119. User Preference Tests

Test:

- Theme persists
- Locale persists
- Timezone persists
- Unauthorized users cannot modify another user's preferences through self-service endpoints

---

# 120. Audit Tests

Test:

- Important operations create audit entries
- Actor is correct
- System actor is represented correctly
- Old/new data is correct where relevant
- Audit records are not editable
- Audit authorization works

---

# 121. Seeder Tests / Verification

Verify that:

```text
DatabaseSeeder
```

does not create demo business data.

Verify that:

```text
DemoSeeder
```

creates a comprehensive working demonstration environment.

Demo seeding should be deterministic enough that developers know what states to expect.

---

# 122. Laravel Pint

Use Laravel Pint if available in the project.

Before completion run:

```bash
vendor/bin/pint
```

Do not leave formatting inconsistencies.

---

# 123. Final Test Run

Before considering implementation complete, run the complete test suite.

For example:

```bash
php artisan test
```

or the appropriate Pest command configured by the project.

Resolve failures.

Do not claim completion with known failing tests.

---

# 124. Code Cleanliness

Do not leave:

- `dd()`
- `dump()`
- Temporary debug output
- Placeholder TODO implementations
- Dead classes
- Commented experimental code
- Unused imports
- Empty generated controllers
- Unused Blade components
- Unused Actions
- Unnecessary abstractions

Comments should primarily explain why a non-obvious decision exists.

Do not narrate obvious code.

---

# 125. Database Constraints

Do not rely only on PHP validation for invariants that the database can safely reinforce.

Use appropriate:

- Foreign keys
- Unique constraints
- Nullability
- Indexes

Examples:

- Unique user email
- Correct foreign-key behavior
- Index `next_send_at`
- Index delivery dates/statuses where useful
- Duplicate scheduled-occurrence protection

Design indexes based on actual expected queries.

Do not add indexes blindly to every column.

---

# 126. Query Efficiency

Prevent obvious N+1 queries.

Use eager loading intentionally.

Upcoming notifications and delivery history should remain efficient with realistic data volumes.

Do not prematurely introduce caching.

First make database queries correct and appropriately indexed.

---

# 127. Mass Assignment

Be explicit about model assignment strategy.

Do not accidentally expose sensitive fields through uncontrolled mass assignment.

Keep Actions responsible for deciding which validated DTO values are persisted.

---

# 128. Transactions

Use database transactions for business operations where multiple writes must succeed or fail together.

Examples may include:

- Client creation with initial schedules
- Role permission synchronization
- Import
- Delivery processing where history and schedule state must remain consistent

Do not wrap every single trivial update in a transaction unnecessarily.

---

# 129. Error Handling

Fail clearly.

Do not hide unexpected exceptions.

User-facing failures should produce understandable feedback without exposing sensitive technical details.

Detailed operational errors may be logged through Laravel.

Delivery failure details stored for authorized users should be useful but should not expose application secrets.

---

# 130. Logging

Use Laravel logging for technical/system problems.

Do not use the generic audit log as a replacement for technical logging.

Keep these concepts separate:

```text
Audit Log
    = who changed business/application state

Laravel Logs
    = technical diagnostics

Delivery History
    = what client email was actually attempted/sent
```

---

# 131. No Generic CRUD Abstraction

Do not build something such as:

```text
BaseCrudController
CrudService
CrudRepository
ResourceDefinition
FieldSchema
GenericFormBuilder
```

just because several resources share CRUD operations.

Use Laravel's conventions and reusable UI components.

The business code for each resource should remain obvious.

---

# 132. No Premature Domain Generalization

Do not rename client notification concepts to vague names such as:

```text
Automation
Workflow
JobDefinition
RecurringEntity
ScheduledAction
Messageable
```

without a real need.

Use domain-specific names.

Prefer:

```text
ClientNotificationSchedule
ClientNotificationDelivery
ClientEmailTemplate
```

Clarity beats hypothetical reuse.

---

# 133. Internal Notification Naming

Avoid ambiguity between:

```text
Client email notifications
```

and:

```text
Laravel notifications for Belo Cadence users
```

Use explicit technical names for client communication.

Do not create a generic application model simply named:

```text
Notification
```

for client schedules.

Laravel's own notifications table/system can retain its framework terminology.

---

# 134. Implementation Process

Before writing application code:

1. Inspect the newly created Laravel project
2. Confirm existing Laravel structure
3. Inspect installed dependencies
4. Inspect Tailwind setup
5. Inspect Pest setup
6. Inspect authentication setup
7. Do not replace existing working configuration unnecessarily

Then create a concise implementation plan internally and implement the project in coherent stages.

Do not generate hundreds of disconnected files before understanding how they fit together.

---

# 135. Implementation Stages

Implement in this order unless a technical dependency makes a small adjustment clearly preferable.

## Stage 1 — Foundation

- Authentication
- Disable/remove registration
- Spatie permission
- Permissions
- Initial roles/admin
- Translation structure
- Design tokens
- Base layout
- Blade components
- Theme support

## Stage 2 — Users and Administration

- User management
- Role management
- Policies
- Profile
- Preferences
- Application settings

## Stage 3 — Clients

- Migration
- Model
- Value Objects/casts
- DTOs
- Requests
- Actions
- Policy
- CRUD views
- Factory
- Tests

## Stage 4 — Cadence Scheduling

- Template enum/catalog
- Static Blade email templates
- Schedule model
- Recurrence enum
- Recurrence logic
- Schedule CRUD
- Upcoming overview
- Tests

## Stage 5 — Sending and Delivery History

- Mailable
- Sending Action
- Due-processing Action
- Artisan command
- Scheduler
- Duplicate protection
- Delivery snapshots
- Delivery history UI
- Failure handling
- Tests

## Stage 6 — Internal Notifications

- Failed-delivery internal notification
- Notification bell
- Read/unread behavior

## Stage 7 — Default Client Notifications

- Application default setup
- Apply during client setup
- Tests

## Stage 8 — CSV Import and Export

- Reusable CSV primitives
- Resource-specific exporters
- Resource-specific importers
- Import templates
- Permissions
- Tests

## Stage 9 — Audit

- Audit model
- RecordAuditAction
- Integration with important operations
- Audit UI
- Export
- Tests

## Stage 10 — Demo Data

- Factories
- Demo-specific seeders
- Complete DemoSeeder

## Stage 11 — Final QA

- All tests
- Pint
- Authorization review
- Responsive review
- Accessibility review
- Dark mode review
- Empty-state review
- Demo data review
- Remove unused code

---

# 136. UI Review Requirements

Before finishing, manually review each major page in:

```text
Light mode
Dark mode
Desktop
Mobile
```

Review at least:

- Login
- Dashboard
- Client list
- Client create/edit
- Client detail
- Schedule list
- Schedule form
- Upcoming notifications
- Delivery history
- Delivery details
- Users
- Roles
- Settings
- Audit log
- Profile/preferences

Look for inconsistent:

- Spacing
- Typography
- Control sizes
- Radius
- Colors
- Table behavior
- Empty states
- Actions
- Mobile overflow

Fix inconsistencies rather than treating each page as an isolated design.

---

# 137. Maintainability Review

Before completion, review the project as if handing it to another developer.

Ask:

- Can the domain be understood from class names?
- Are Actions easy to locate?
- Are DTOs clearly associated with operations?
- Are permissions predictable?
- Are Blade components understandable?
- Are there unnecessary abstractions?
- Is there duplicated business logic?
- Are important invariants tested?
- Can a developer add a new static email template easily?
- Can a developer add a new permission predictably?
- Can a developer add a new notification frequency later without rewriting the application?
- Can design colors and typography be changed centrally?

Refactor anything that fails these questions.

---

# 138. Definition of Done

The implementation is not complete merely because pages exist.

Belo Cadence V1 is complete when all of the following work end-to-end:

1. An administrator can log in
2. Guests cannot access the application
3. Public registration does not exist
4. Permissions control navigation and backend operations
5. Administrators can manage users
6. Administrators can manage roles and permission assignments
7. Authorized users can manage clients
8. Authorized users can create client notification schedules
9. They can select only source-controlled available Blade email templates
10. Monthly/yearly/one-time recurrence works correctly
11. Upcoming notifications are clearly visible
12. Laravel scheduler can process due notifications
13. Duplicate scheduled occurrences are protected
14. Emails use the correct client information
15. Every send attempt has persistent history
16. The exact recipient is stored
17. The exact subject is stored
18. The exact rendered email is stored
19. Success is visible
20. Failure is visible
21. Failed deliveries create useful internal attention
22. Historical delivery data does not change later
23. User preferences work
24. Light/dark/system mode works
25. Application settings work
26. Default notification configuration for new clients works
27. Applicable tables can export CSV
28. Mutable applicable resources can import CSV
29. Import templates are downloadable
30. Imports are permission-gated and validated
31. Important changes are auditable
32. DatabaseSeeder contains only essential environment data
33. DemoSeeder provides comprehensive test/demo states
34. The application is responsive
35. The design is consistent
36. User-facing text is translation-ready
37. Pest tests pass
38. Pint passes
39. No debugging/dead implementation code remains

---

# 139. Decision Priority When Something Is Unspecified

If a requirement is not explicitly defined, make decisions using this order:

1. Protect data integrity
2. Keep behavior explicit
3. Follow Laravel conventions
4. Prefer the simplest viable V1
5. Prefer maintainability
6. Prefer strict typing
7. Prefer testability
8. Avoid additional dependencies
9. Avoid speculative future functionality

Do not choose a more abstract or generic solution merely because it might support hypothetical future requirements.

---

# 140. Final Principle

The finished application should feel like **one intentionally designed product**, not a collection of generated CRUD pages.

The architecture should feel like **Laravel**, not like a custom framework built on top of Laravel.

The code should be easy to review.

The UI should be consistent.

The domain should be small and explicit.

Historical client communication must be trustworthy.

The most important engineering principles for Belo Cadence are:

> **Clarity, consistency, explicit behavior, maintainability and data integrity.**