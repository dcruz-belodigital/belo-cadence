# User Guide

Belo Cadence keeps track of the email your clients should receive on a schedule: what
goes out, when it goes out, whether it actually went, and exactly what was sent.

## Getting started

You reach Belo Cadence with an account somebody created for you — there is no sign-up
page. If you cannot sign in, ask an administrator to check that your account is active.

What you can see and do depends on the **roles** you hold. If a section described here is
missing from your sidebar, your roles do not include it; that is expected, not a fault.

Your first stop is the **Dashboard**. It answers one question: what needs attention?

- **Due today** — occurrences whose moment has arrived, including anything overdue.
- **Due in the next 7 days** — what is coming.
- **Sent this month** and **Failed this month** — how sending has gone.

Under the metrics you get the next notifications, the most recent failures, and recent
activity. Each panel links to the full list.

## Clients

A **client** is an organisation that receives email. Belo Cadence keeps them deliberately
simple: a name, one email address, a status, and internal notes.

- **Active** clients receive their scheduled email. **Inactive** clients keep all their
  settings and history but are never emailed.
- **Internal notes** are never sent to anybody. They are for your team.
- **Archiving** a client removes it from the working list and switches off its schedules.
  Nothing is deleted: every email already sent stays in delivery history, and the client
  can be restored from its own page.

Tick *Include archived* on the client list to find an archived client again.

## Notification schedules

A **schedule** answers: which email does this client receive, and when?

You choose one of the email templates, a frequency, and the first send date and time.
From then on Belo Cadence works out each following occurrence itself.

- **One time** — sent once, then the schedule closes itself.
- **Monthly** — every month on the same day.
- **Yearly** — every year on the same date.

The first send date is the **anchor** of the recurrence, and the day of the month is
never lost. A monthly schedule anchored on the 31st falls back to the last day of shorter
months and returns to the 31st afterwards. A yearly schedule anchored on 29 February uses
28 February in common years and 29 February again in leap years.

Times are always shown and entered in **your** timezone, which you set in your profile.
The recurrence itself is calculated in the application's timezone so that a schedule keeps
its intended local time all year.

**Disabling** a schedule stops it sending but keeps its anchor, so you can resume later.
Enabling it again picks up from the next future occurrence — occurrences missed while it
was off are not sent in a burst.

**Deleting** a schedule removes it from the working lists and keeps everything it already
sent.

## Upcoming

**Upcoming** is the answer to "what is going out next?", nearest first. Narrow it to
today, the next 7 days or the next 30 days, or filter by client, template, frequency and
state. Whatever you are looking at can be exported as it stands.

## Email templates

Templates are part of the application, not something you edit in the browser. That is
deliberate: it means the wording of a client email is reviewed like any other change and
cannot be altered by accident.

Open **Cadence → Email templates** to read each one exactly as a client receives it,
with a sample client filled in. You can also preview a template from the schedule form
while you are choosing one.

Adding a template is a small development task: an entry in the catalogue, a view, the
wording, and a test.

## Delivery history

Every send attempt is recorded, successful or not. A delivery is **evidence**: it stores
the recipient, the sender, the subject and the exact message that was produced, and none
of it is ever recalculated. If a client's name or address changes tomorrow, or the sender
changes, or a template is reworded, an old delivery still shows what actually went out.

Open a delivery to see the message as it was sent, when it was scheduled, when it was
attempted, and — if it failed — why.

Delivery history cannot be edited or imported, by design. It can be searched, filtered
and exported.

Each occurrence is sent **once**. Running the scheduler twice, or by hand while it is
already running, cannot produce a second email for the same occurrence.

## When something fails

A failed send is kept, marked as failed with the reason, and raises a notification for
everybody who can see delivery history. The schedule then moves on to its next
occurrence, so one bad afternoon does not block the whole series.

Look at the failure message on the delivery page first: it usually names the problem
(a mail server that refused the connection, a rejected address). Once the cause is fixed,
the next occurrence sends normally; if the missed message still matters, create a
one-time schedule for it.

## Importing and exporting

Every list can be exported in two shapes:

- **This view as CSV** — the columns you see, formatted for a person, honouring your
  current search, filters and sorting.
- **Raw CSV** — every stored column with raw values and ISO dates, for another system.

Clients and users can also be **imported**:

1. **Download the template** so your columns match.
2. **Upload** your file.
3. **Match the columns** — Belo Cadence matches by name automatically and shows you the
   first rows, so you can see what it understood before anything is saved.
4. **Import.** Every row is checked with the same rules the forms use. If one row is
   wrong, nothing at all is imported and you get told which line and why.

Leave the `id` column empty to create a record; fill it with an existing id to update
that record. Imported records go through the same steps as records entered by hand, so
they appear in the audit log too.

Delivery history and the audit log are deliberately **not** importable: they are the
record of what happened.

## Users and roles

Accounts are created inside Belo Cadence. A person can hold more than one role, and a
role is a bundle of permissions.

- Permissions are part of the application; you choose which ones a role grants.
- Deactivating somebody stops them signing in and keeps everything they did.
- You cannot deactivate yourself, and the application refuses any change that would leave
  nobody able to manage users and roles.

## Audit log

The audit log records who changed what: clients, schedules, users, roles, settings and
the default notifications, with the values before and after. Work done by the scheduler
has no person behind it and is recorded as **System**.

It is read-only. Ordinary page views are not recorded — only changes worth keeping.

## Settings

**Application settings** hold the application's name, the default language and timezone,
the **default theme**, and the name and address client email is sent from. Changing the
sender affects future email only. Mail server credentials are not editable here on
purpose: they belong in the server's environment configuration.

The default theme is what everybody sees until they pick one of their own, so changing it
re-dresses the application for the whole team at once.

**Default notifications**, further down the same page, are the schedules your team
usually applies to a new client. They are offered when a client is created; applying one
**copies** it onto that client. Changing this list later never alters a schedule that
already exists.

## Your profile

Your profile holds your name, your password and your preferences:

- **Colour scheme** — system, light or dark. Also switchable from the account menu at any
  time.
- **Theme** — which of the looks below the application wears for you. Leave it unset to
  follow whatever an administrator has set as the default, including later changes.
- **Language** and **Timezone** — the timezone decides how every date and time in the
  application is shown to you.

Only you see your own theme and colour scheme; neither changes anything a client receives.

Your roles and whether your account is active are managed by an administrator.

## Themes

A theme decides colour, corner shape and lettering together. Each one is drawn in both
light and dark, so a theme and a colour scheme are separate choices.

- **Iris** — minimal and modern, in soft violet. The default.
- **Cappuccino** — classic and square-cut: warm paper, espresso ink and a serif for
  headings.
- **Bubblegum** — loud, round and cheerful.
- **Graphite** — sober near-monochrome, ink on paper, with colour kept for the things that
  report a state.
- **Cathode** — a terminal: monospaced throughout, square, phosphor green.

The mark in the sidebar — a folded paper plane — is drawn in the theme's own colour and
corner shape, so you can tell at a glance which theme you are wearing.
