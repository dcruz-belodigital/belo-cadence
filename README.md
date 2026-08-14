# Belo Cadence

Recurring client email, kept honest. Belo Cadence tracks which of your clients should
receive which message and when, sends it on schedule, and keeps a permanent record of
exactly what went out — the recipient, the sender, the subject and the message itself,
frozen at the moment of sending.

Built with Laravel 13, Tailwind CSS 4 and Alpine. English and European Portuguese.

- **Clients** — the organisations you send to.
- **Schedules** — one-time, monthly or yearly, anchored to a first send date.
- **Delivery history** — every attempt, successful or not, and why it failed.
- **Templates** — source-controlled, so wording is reviewed like any other change.
- **Users, roles and audit log** — 36 permissions, and a record of who changed what.
- **Themes** — five, each drawn in light and dark, set per installation or per person.

`APP_RESUME.md` covers what is working in more detail, including what has to be switched
on before real email is delivered.

## Requirements

PHP 8.3+, Composer, Node 22+ and npm. SQLite by default; any database Laravel supports
will do.

## Installation

```bash
git clone <repository-url> belo-cadence
cd belo-cadence
composer setup
```

That is the whole installation. `composer setup` installs the PHP and JS dependencies,
creates `.env` from `.env.example`, generates the application key, runs the migrations —
creating the SQLite file if it is not there — and builds the front end.

Then seed. Pick one:

```bash
php artisan db:seed                      # permissions, roles, an administrator, settings
php artisan db:seed --class=DemoSeeder   # the above plus clients, schedules and history
```

`DemoSeeder` is the one to use for a look around: it creates clients, schedules,
delivery history, notifications and audit entries, and accounts at every permission
level. Every demo account signs in with `demo-password-123`.

The first administrator's password comes from `CADENCE_ADMIN_PASSWORD` in `.env`. Leave
it empty and a strong one is generated and printed once during seeding.

## Running it

```bash
composer dev
```

That starts the web server, the queue worker, the log viewer and Vite together. Visit
the URL it prints.

## Testing

```bash
composer test
```

Clears the config cache first, then runs the suite — 634 tests, all passing. Add
arguments as usual, for example `composer test -- --filter=ThemeTest`.

## Sending email for real

Out of the box `MAIL_MAILER=log`, so messages are written to `storage/logs/laravel.log`
rather than delivered. To send properly:

1. Point the `MAIL_*` variables at a real SMTP service.
2. Run the scheduler, which is what actually sends. In production, one cron entry:
   ```
   * * * * * cd /path/to/belo-cadence && php artisan schedule:run >> /dev/null 2>&1
   ```
   Locally, `php artisan schedule:work` — or just `composer dev`, which includes it.
3. Set the sender name and address under **Settings** in the application. Those win over
   `.env` for client email.

Due notifications are processed hourly, so a message goes out in the hour it falls due.
Each occurrence is sent once, even if two runs overlap.

## Working on it

- `vendor/bin/pint` — formats PHP to the project's style. Run it before committing.
- `npm run build` — rebuilds the front end after changing CSS or Blade views.
- `.ai/rules/` — the settled decisions and standing constraints for this codebase,
  grouped by the paths they apply to. Read the ones matching what you are about to
  change; several are load-bearing.

Themes live in `resources/css/themes.css`, one block of variables each. Translations live
in `lang/<locale>`, and the guide and changelog in `resources/docs/<locale>`; a key or a
document present in one language and missing in another fails the test suite.
