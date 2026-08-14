# Belo Cadence — where the application stands

Recurring client email: who receives what, when it goes out, and a permanent record of
what was actually sent. Everything below is built and covered by tests (634 passing).

## What works today

| Area | State |
| --- | --- |
| Clients | Create, edit, archive, restore. Active/inactive, internal notes. |
| Notification schedules | One-time, monthly, yearly. Enable/disable, edit, delete. |
| Upcoming | What goes out next, filtered by range, client, template, frequency, state. |
| Delivery history | Every attempt, with the exact message that was produced. Read-only. |
| Email templates | Three, readable exactly as a client receives them. Source-controlled. |
| Default notifications | Offered when creating a client. A section of Settings. |
| Users, roles, permissions | 36 permissions; Administrator and Viewer ship by default. |
| Audit log | Who changed what, values before and after. Scheduler work logs as System. |
| Import / export | CSV both ways for clients and users, with a column-matching step. |
| Notifications | In-app alert to everyone who can see delivery history when a send fails. |
| Themes | Five, each in light and dark. Set per application and per person. |
| Languages | English and European Portuguese, interface and documents. |

## Email: how it works

1. **The scheduler** runs `cadence:process-notifications` **hourly**, with overlap
   protection. A notification therefore goes out in the hour it falls due, not to the
   minute.
2. It picks up schedules whose `next_send_at` has arrived, **claims each occurrence in a
   locked transaction**, and moves the schedule to its next date.
3. The message is rendered and **sent synchronously** — `Mail::send()`, not queued.
4. The attempt is recorded either way: recipient, sender, subject and the exact HTML,
   frozen. Nothing is recalculated later, so old deliveries stay true after a client,
   sender or template changes.
5. A failure is stored with its reason, raises a notification, and the schedule still
   moves on to its next occurrence.

Each occurrence is sent **once**. The claim plus a unique index on
(schedule, occurrence) makes a double send impossible even if two runs overlap.

Recurrence is anchored to the first send date and calculated in the application timezone
(**UTC**). A monthly schedule anchored on the 31st falls back to the last day of shorter
months; a yearly one anchored on 29 February uses 28 February in common years.

## Are emails actually being delivered?

**No — not in this environment.** Two things are switched off:

- `MAIL_MAILER=log`, so messages are written to `storage/logs/laravel.log` instead of
  being handed to a mail server. The application records them as *sent* because handing
  off succeeded; nothing reaches a real inbox.
- **No scheduler process is running.** `schedule:list` shows both commands registered,
  but nothing invokes them, so due notifications sit unsent until the command is run by
  hand.

To send for real:

1. Set the `MAIL_*` variables to a real SMTP service.
2. Run the scheduler — `php artisan schedule:work` locally, or a cron entry calling
   `php artisan schedule:run` every minute in production.
3. Set the sender name and address under **Settings** (not `.env` — the application
   settings win for client email).

Everything else — the recurrence maths, the once-only guarantee, the failure handling,
the permanent record — is already working and does not change when real mail is switched
on.

## Not built

Queued sending (mail goes out inline, so a slow mail server slows the run), retrying a
failed delivery from the interface, and sending a client email on demand outside a
schedule.
