# Changelog

Every release of Belo Cadence, newest first. Add a new `##` heading with the release date
for each one, and group the entries under **New features**, **Changes** or **Fixes**.

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
