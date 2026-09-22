# Changelog

## 22 September 2026

### New features

- **Client attributes can hold a file.** A new **File** type takes an upload of up to
  10 MB — documents, spreadsheets, presentations, plain data files, images and zip
  archives. A repeating row can hold one too, so "one file per certificate" is a single
  attribute. Replacing or removing a file deletes the old one for good, from the record
  and from storage alike, and deleting a file attribute takes every file uploaded for it.
- **Uploaded files are never reachable by a link on their own.** They are served only
  through Belo Cadence, to somebody signed in who may read that client. Share the address
  and anybody else gets a login page.
- **Notifications can carry attachments.** Tick a client's file attributes under
  **Attachments** on a schedule, or when sending by hand; a file inside repeating rows
  attaches one file per row. Each email carries whatever the client holds at the moment it
  goes out, so replacing a contract updates every notification that attaches it. A file
  that cannot be found fails the delivery rather than sending an email without it, and
  delivery history records the name of everything that went out.

## 18 September 2026

### New features

- **Clients can hold whatever else your team records about them.** Administration →
  Client attributes is where you define the fields: text, numbers, dates, yes/no, a choice
  from a list, web and email addresses, and repeating rows — a set of fields filled in as
  many times as needed, which can themselves contain repeating rows. Every active
  attribute appears on the client form, on the client's page, and in both CSV files.
- **Attributes can be imported and exported.** Each one gets its own column, named after
  its identifier. A column you do not match during an import is left untouched, so a file
  that only corrects email addresses changes nothing else.
- **Email templates can read them.** A template that leaves a blank — the annual reminder
  now leaves two — offers a picker on the schedule form: fill it from one of the client's
  attributes, including a single field inside repeating rows, or type a value used by that
  notification alone. A blank pointed at an answer the client does not have records the
  delivery as failed rather than sending an email with a hole in it.
- **Retiring an attribute keeps its answers.** Deactivating hides it everywhere and loses
  nothing; deleting says how many clients it would affect first.

## 21 August 2026

### New features

- **Notifications no longer have to be about a client.** A schedule now sends either to a
  client, as before, or to a **recipient list**: you give the notification a name and the
  addresses it goes to, and it belongs to no client at all. That is how anything which is
  not about one client gets scheduled — an internal digest, a note to a supplier, a
  reminder for your own team. Create either kind from Cadence → Notification schedules →
  New notification, or a client one from the client's own page. A schedule never changes
  what it sends to.
- **Templates now say who they are written for.** Each template is for a client, for a
  recipient list, or for any schedule, and a form only offers the ones that suit what you
  are sending to — so a client can never be sent wording written for a mailing list. Two
  new recipient-list templates ship with it: **Status update** and **Action required**.
- **A Blank template**, usable by either kind, where you write the subject and the message
  on the schedule itself. It is plain text, sent exactly as typed, inside the normal email
  frame. Every other template's wording stays in the application, reviewed like any other
  change.
- **Send any notification to anyone, at any time.** Send now on any schedule sends that
  schedule's own message to its own recipients immediately, without touching its
  recurrence. The ad-hoc Send now form additionally lets you type a name and addresses on
  the spot, so a one-off notice needs no schedule to exist first. Both are recorded as
  manual sends, naming the person who sent them.
- **One address, one delivery.** A notification going to five people is now five entries in
  delivery history rather than one, so a single rejected address shows up on its own
  instead of hiding behind the four that arrived. Each delivery records what it was sent to
  — a client, or the name the list had at the time — snapshotted like everything else.

### Changes

- **"Client notification" is now just "notification"** throughout the application, because
  a notification need not involve a client any more. Schedules, delivery history, their
  permissions and the audit log all follow. Existing audit entries, permission grants and
  history are migrated, so nothing is lost and no role loses access — but a permission
  named `client-notifications.*` in any external integration is now `notifications.*`.
- **A Sends to filter** on the schedules list and Upcoming, to look at only client
  schedules or only recipient lists. Both exports carry the distinction.
- **The schedules list and Upcoming name the notification** rather than only its client, so
  a recipient list reads properly there.

- **A source filter on delivery history**, to look at only the sends a person asked for or
  only the scheduler's own. Both exports carry the distinction.
- **Sending by hand needs its own permission**, so it can be granted separately from
  managing schedules. Only active clients are ever offered: marking a client inactive still
  means "stop emailing them", and that holds for Send now too.

## 14 August 2026

### New features

- **Belo Cadence speaks Portuguese.** The whole interface, the client email, this guide
  and this changelog are all available in European Portuguese. Switch language from the
  account menu or from your profile; administrators set the default for everyone under
  Settings.
- **Five themes to choose from.** Iris (minimal and modern, the default), Cappuccino
  (classic and square-cut), Bubblegum (loud and round), Graphite (near-monochrome) and
  Cathode (a monospaced terminal). Each decides colour, corner shape and lettering
  together, and each is drawn in both light and dark. Administrators set the default for
  everyone under Settings; anyone can pick their own under their profile, or leave it
  unset to follow the default as it changes.
- **Belo Cadence is here.** Recurring client email in one place: clients, the schedules
  that decide what they receive, and a permanent record of everything that was sent.
- **Schedules with three frequencies.** One time, monthly and yearly. The first send date
  is the anchor of the recurrence, so a monthly schedule set on the 31st falls back to the
  last day of shorter months and returns to the 31st afterwards, and a yearly schedule set
  on 29 February uses 28 February in common years.
- **Upcoming.** What is going out next, nearest first, narrowed to today, the next 7 days
  or the next 30 days, and filterable by client, template, frequency and state.
- **Delivery history you can trust.** Every attempt stores the recipient, the sender, the
  subject and the exact message that was produced. Nothing is recalculated later, so an
  old delivery still shows what actually went out after a client, a sender or a template
  changes. Each occurrence is sent once, even if the scheduler overlaps.
- **Failures that ask for attention.** A failed send is kept and marked with its reason,
  raises a notification for everybody who can see delivery history, and the schedule moves
  on to its next occurrence.
- **Email templates as source code.** The wording of a client email is reviewed like any
  other change instead of being edited in a browser, and each template can be read exactly
  as a client receives it under Cadence → Email templates.
- **Default notifications.** The schedules your team usually applies to a new client are
  offered while creating one. Applying a default copies it, so changing the list later
  never alters a schedule that already exists.
- **CSV in and out.** Every list exports either as the view you are looking at or as raw
  data for another system. Clients and users import from CSV with a column-matching step
  and a preview of your rows; if one row is wrong, nothing is imported.
- **Users, roles and permissions.** Roles bundle permissions, people can hold several, and
  the application refuses any change that would leave nobody able to manage access.
- **Audit log.** Who changed what, with the values before and after. Work done by the
  scheduler is recorded as System rather than attributed to a person.
- **Light, dark and system colour schemes**, your own timezone and language, all from your
  profile or the account menu.
- **This guide and this changelog**, written in markdown and kept with the application.

### Changes

- **A new mark.** Belo Cadence is now a folded paper plane rather than a clock, in the
  sidebar, on the sign-in screen and in the browser tab. In the application it is drawn
  in the current theme's colour and corner shape; in the tab it stays a neutral ink that
  inverts with the browser's own light or dark appearance.
- **Due notifications are processed hourly** rather than every five minutes. These are
  reminders on a monthly or yearly cadence, so the hour a message goes out in is what
  matters; a schedule set for 09:00 still sends at 09:00.
- **The import pages lead with the file.** Choosing a file is now the first thing on the
  page, the button simply says Continue, and the template is offered as a link beside it
  rather than as a second button. The column reference follows underneath. The step-by-step
  explanation has moved to the user guide, which every import page now links to.
- **Default notifications are now a section of Settings** rather than a page of their
  own, so everything an administrator configures once is in one place. Who may change
  them is unchanged: the section only appears for people who could reach the old page,
  and it still records its own audit entry.
- **"Theme" now means the look of the application**, and the light/dark switch is called
  **colour scheme**. The control in the account menu is unchanged — only its name is.

### Fixes

- Buttons that submit or toggle now show the hand pointer, so the language, colour scheme
  and sign-out controls no longer look unclickable.
- **Sign out** is drawn in one colour rather than red text beside a grey icon.
