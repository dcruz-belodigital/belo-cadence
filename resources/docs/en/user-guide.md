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

A **client** is an organisation that receives email. Every client has a name, one email
address, a status and internal notes — and whatever else your team has chosen to record
about them, through **client attributes**.

- **Active** clients receive their scheduled email. **Inactive** clients keep all their
  settings and history but are never emailed.
- **Internal notes** are never sent to anybody. They are for your team.
- **Archiving** a client removes it from the working list and switches off its schedules.
  Nothing is deleted: every email already sent stays in delivery history, and the client
  can be restored from its own page.

Tick *Include archived* on the client list to find an archived client again.

## Client attributes

Belo Cadence does not decide what else you need to know about a client. **Administration →
Client attributes** is where your team defines it: a name, a type, and whether it has to be
filled in. Every active attribute then appears on the client form, in the order you give
them.

The types are text, long text, number, date, yes/no, a choice from a list, a web address,
an email address, a **file**, and **repeating rows** — a set of fields that can be filled
in as many times as needed, such as a list of contacts. A repeating row can itself contain
repeating rows, up to three levels deep, and a row can hold a file of its own.

A field inside a repeating row needs a type, but not a name. Leave the name empty and its
values are shown on their own rather than labelled, which turns a row of one field into a
plain list — a list of domains, say.

A few things worth knowing:

- The **identifier** is set from the name when the attribute is created and never changes.
  It is what the column is called in CSV files, so renaming an attribute never breaks a
  file or a pipeline that already uses it.
- The **type cannot be changed** afterwards, because every answer already recorded is
  stored in that shape. To change it, deactivate the attribute and create another.
- Removing an option from a list stops it being offered, but a client already using it
  keeps its answer and stays editable.
- **Deactivating** an attribute hides it from the client form, the client page and both
  CSV files while keeping every answer. Switch it back on and they are all still there.
- **Deleting** one really does delete the answers with it. The confirmation says how many
  clients are affected.

Marking an attribute required stops a client being saved without it. Clients created
before that was ticked are left alone until somebody next edits them.

### Files

A **file** attribute takes an upload of up to 10 MB — documents, spreadsheets,
presentations, plain data files, images and zip archives. Choosing a new file replaces the
one that was there, and **Remove file** clears it; either way the file that is no longer
used is deleted for good, from the record and from storage alike.

Uploaded files are never reachable by a link on their own. They are served only through
Belo Cadence, to somebody signed in who may read that client — share the address with
anybody else and they get a login page, not a file. Deleting a file attribute deletes
every file uploaded for it, on every client.

Files cannot be imported: a spreadsheet has no bytes to carry, so an attribute that holds
one is simply left out of the import. Both exports name the file so a reader knows what is
there.

## Notification schedules

A **schedule** answers: which email goes out, to whom, and when?

Every schedule sends to one of two things, and it is decided when you create it:

- **A client.** The email goes to that client's address and the schedule is tracked under
  them, on their own page.
- **A recipient list.** You give the notification a name and the addresses it goes to.
  It belongs to no client, which is how anything that is not about one client gets
  scheduled — an internal digest, a note to a supplier, a reminder for your own team.

Create a client schedule from the client's page, and either kind from **Cadence →
Notification schedules → New notification**. A schedule never changes what it sends to:
editing one lets you change its name, its addresses, its template and its timing, but a
client schedule stays a client schedule.

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

**Send now**, on any schedule, sends that schedule's own message immediately to its own
recipients. The recurrence is untouched: nothing is rescheduled and no occurrence is used
up. It is recorded as a manual send.

A client schedule only sends while its client is active and not archived — that applies to
Send now as well. A recipient list answers to nobody's status.

## Upcoming

**Upcoming** is the answer to "what is going out next?", nearest first. Narrow it to
today, the next 7 days or the next 30 days, or filter by client, template, frequency and
state. Whatever you are looking at can be exported as it stands.

## Email templates

Templates are part of the application, not something you edit in the browser. That is
deliberate: it means the wording of an email is reviewed like any other change and cannot
be altered by accident.

Every template is written for a particular reader, and that decides where it can be used:

- **For a client** — the wording speaks to the client it is about. Only client schedules
  may use these.
- **For a recipient list** — the wording speaks to whoever was put on the list. Only
  recipient-list schedules may use these.
- **For any schedule** — the **Blank** template, which either kind may use.

The schedule form only offers the templates that suit what you are sending to, so a
client cannot be sent wording written for a mailing list.

**Blank** is the one exception to wording living in the application: it ships with no copy
at all, and you write the subject and the message on the schedule itself. It is plain text
— leave a blank line between paragraphs — and it is sent exactly as typed, inside the
normal email frame. Use it for the one-off that no template covers; use a real template
for anything you send repeatedly, because that wording gets reviewed.

Open **Cadence → Email templates** to read each one exactly as it arrives, with an example
name filled in and a note of who it is written for. You can also preview a template from
the schedule form while you are choosing one.

Adding a template is a small development task: an entry in the catalogue, its audience, a
view, the wording, and a test.

Some templates leave **blanks** for you to fill in, such as a renewal date. When you pick
one of those on a schedule — or when sending by hand — a picker appears for each blank.
Point it at one of the client's attributes, including a single field inside repeating
rows, or choose *Type a value* and write something used by that notification alone.

If a blank is pointed at an attribute the client has no answer for, the notification is
**not** sent: the attempt is recorded in delivery history as failed, with the reason, so
nobody receives an email with a hole in it.

### Attachments

Any notification about a client can also carry that client's files. Tick them under
**Attachments** on the schedule form, or on the send-by-hand form — the list offers every
file attribute, including a file inside repeating rows, which attaches one file per row.

An attachment is a pointer, not a copy: each email carries whatever that client holds at
the moment it goes out, so replacing a client's contract updates every notification that
attaches it without anybody editing a schedule. A file that cannot be found fails the
delivery in the same way an unfilled blank does, and delivery history records the name of
everything that went out.

A schedule sending to a recipient list has no client whose files could be read, so it
offers no attachments.

## Sending by hand

Sometimes something needs to go out now, and no schedule covers it. There are two ways.

**From a schedule.** Open the schedule and press **Send now**. It sends that schedule's
own message to its own recipients, and changes nothing about its recurrence.

**Without a schedule.** Open **Cadence → Delivery history → Send now**, or use **Send
now** on a client's own page. Choose either a client or type the addresses yourself, pick
a template, and confirm — no schedule has to exist first. The email leaves immediately.

Only **active** clients are offered. Marking a client inactive, or archiving them, is how
you say "stop emailing them", so a manual send will not override it. Typed addresses are
yours to get right; nothing checks them beyond being valid addresses.

A manual send is recorded in delivery history exactly like a scheduled one, marked
**Manual** and naming the person who sent it. Nothing about any schedule changes: no
anchor moves, no occurrence is used up. Sending twice sends twice — there is no occurrence
to be duplicated, so asking again means asking again.

You can narrow delivery history to manual or scheduled sends with the **Source** filter,
and the distinction comes along in both exports.

## Delivery history

Every send attempt is recorded, successful or not. A delivery is **evidence**: it stores
the recipient, the sender, the subject and the exact message that was produced, and none
of it is ever recalculated. If a client's name or address changes tomorrow, or the sender
changes, or a template is reworded, an old delivery still shows what actually went out.

**One address, one delivery.** A notification going to five people is five deliveries, one
per address. That is what lets a single rejected address show up on its own instead of
hiding behind the four that arrived, and it is why a recipient list produces several rows
for the same occurrence.

Each delivery records what it was **sent to** — a client, or the name the recipient list
had at the time — and that name is snapshotted like everything else, so renaming a list
never rewrites its history.

Open a delivery to see the message as it was sent, when it was scheduled, when it was
attempted, and — if it failed — why.

Delivery history cannot be edited or imported, by design. It can be searched, filtered
and exported.

Each occurrence is sent **once per address**. Running the scheduler twice, or by hand
while it is already running, cannot produce a second email for the same occurrence and the
same recipient.

## When something fails

A failed send is kept, marked as failed with the reason, and raises a notification for
everybody who can see delivery history. The schedule then moves on to its next
occurrence, so one bad afternoon does not block the whole series. When one address on a
recipient list fails, only that address fails — the rest of the list still went out.

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

Custom attributes get a column each, named `attribute_` followed by the attribute's
identifier. A column you do not match is left alone, so a file that only corrects email
addresses never touches anything else. Repeating rows travel as JSON in a single cell,
which is why the **raw** export is the one that can be imported straight back. An
attribute holding an uploaded file is exported by name and cannot be imported.

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

They are offered plainest first, so the further down the list you go the more the theme
has to say for itself.

- **Iris** — minimal and modern, in soft violet. The default.
- **Graphite** — sober near-monochrome, ink on paper, with colour kept for the things that
  report a state.
- **Meridian** — cool and precise: deep marine blue, crisp corners and a technical
  grotesque.
- **Ember** — warm and loud: a near-neutral charcoal page with the colour saved for the
  accent.
- **Cappuccino** — classic and square-cut: warm paper, espresso ink and a serif for
  headings.
- **Folio** — bookish: ivory paper, a serif for the whole page and an oxblood accent.
- **Cathode** — a terminal: monospaced throughout, square, phosphor green.
- **Bubblegum** — loud, round and cheerful.
- **Manuscript** — written out by hand: cream stock, blue-black ink, corners cut square,
  and the whole page — titles, body and labels alike — set in longhand. Figures stay
  monospaced so columns of them still line up.

The mark in the sidebar — a folded paper plane — is drawn in the theme's own colour and
corner shape, so you can tell at a glance which theme you are wearing.
