<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Enums\ColorScheme;
use App\Enums\EmailTemplate;
use App\Enums\EmailTemplateAudience;
use App\Enums\Locale;
use App\Enums\NotificationDeliverySource;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationFrequency;
use App\Enums\NotificationTarget;
use App\Enums\NotificationTimeRange;
use App\Enums\PermissionName;
use App\Models\Audit;
use App\Models\Client;
use App\Models\NotificationSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Lang;

/**
 * Labels are looked up with keys built at runtime, so a missing translation would only
 * show up as a raw key on a page. These tests catch that instead.
 */
function expectTranslated(string $label, string $keyPrefix): void
{
    expect($label)
        ->not->toBe('')
        ->and($label)->not->toStartWith($keyPrefix)
        ->and($label)->not->toContain('.');
}

it('has a label for every client status', function (ClientStatus $status): void {
    expectTranslated($status->label(), 'enums.');
})->with(ClientStatus::cases());

it('has a label for every frequency', function (NotificationFrequency $frequency): void {
    expectTranslated($frequency->label(), 'enums.');
})->with(NotificationFrequency::cases());

it('has a label for every delivery status', function (NotificationDeliveryStatus $status): void {
    expectTranslated($status->label(), 'enums.');
})->with(NotificationDeliveryStatus::cases());

it('has a label for every delivery source', function (NotificationDeliverySource $source): void {
    expectTranslated($source->label(), 'enums.');
})->with(NotificationDeliverySource::cases());

it('has a label for every notification target', function (NotificationTarget $target): void {
    expectTranslated($target->label(), 'enums.');
})->with(NotificationTarget::cases());

it('has a label for every template audience', function (EmailTemplateAudience $audience): void {
    expect($audience->label())->not->toBe('')->not->toStartWith('enums.');
})->with(EmailTemplateAudience::cases());

it('has a label for every email template', function (EmailTemplate $template): void {
    expectTranslated($template->label(), 'enums.');
})->with(EmailTemplate::cases());

it('has a label for every theme', function (ColorScheme $theme): void {
    expectTranslated($theme->label(), 'enums.');
})->with(ColorScheme::cases());

it('has a label for every locale', function (Locale $locale): void {
    expectTranslated($locale->label(), 'enums.');
})->with(Locale::cases());

it('has a label for every time range', function (NotificationTimeRange $range): void {
    expectTranslated($range->label(), 'cadence.');
})->with(NotificationTimeRange::cases());

it('has a label for every audit action', function (AuditAction $action): void {
    expect($action->label())->not->toBe('')->not->toStartWith('enums.');
})->with(AuditAction::cases());

it('has a name and a group label for every permission', function (PermissionName $permission): void {
    expect($permission->label())->not->toBe('')->not->toStartWith('permissions.')
        ->and($permission->groupLabel())->not->toBe('')->not->toStartWith('permissions.');
})->with(PermissionName::cases());

/*
| The blank template deliberately ships no wording: its subject and message are written
| on the schedule, so there is nothing in the lang files to check.
*/
it('has a subject for every email template that brings its own copy', function (EmailTemplate $template): void {
    $subject = $template->subject([
        'application' => 'Belo Cadence',
        'client' => 'A Client',
        'name' => 'A Recipient List',
    ]);

    expect($subject)->not->toBe('')
        ->not->toStartWith('mail.')
        ->toContain('Belo Cadence');
})->with(array_filter(EmailTemplate::cases(), fn (EmailTemplate $template): bool => $template->hasOwnCopy()));

it('has body copy for every email template that brings its own copy', function (EmailTemplate $template): void {
    $lines = Lang::get('mail.notifications.'.$template->value.'.lines', ['application' => 'Belo Cadence']);

    expect($lines)->toBeArray()->not->toBeEmpty();

    foreach ($lines as $line) {
        expect($line)->toBeString()->not->toBe('');
    }
})->with(array_filter(EmailTemplate::cases(), fn (EmailTemplate $template): bool => $template->hasOwnCopy()));

it('has a label for every record type the audit log can refer to', function (): void {
    // Every mapped morph key can appear in auditable_type, so each needs a label.
    foreach (array_keys(Relation::morphMap()) as $morphKey) {
        $label = __('audit.auditable_types.'.$morphKey);

        expect($label)->not->toStartWith('audit.auditable_types.');
    }
});

it('has every date format the application asks for', function (string $format): void {
    $pattern = __('common.formats.'.$format);

    expect($pattern)->not->toStartWith('common.formats.')
        ->and(now()->format($pattern))->not->toBe('');
})->with(['date', 'datetime', 'time', 'month']);

it('renders no raw translation keys on the pages a reader visits', function (): void {
    $client = Client::factory()->withNotes()->create();
    $schedule = NotificationSchedule::factory()->for($client)->create();
    Audit::factory()->forRecord($client)->create(['user_id' => User::factory()]);

    $administrator = administrator();

    $urls = [
        route('dashboard'),
        route('clients.index'),
        route('clients.create'),
        route('clients.show', $client),
        route('clients.edit', $client),
        route('clients.import.create'),
        route('cadence.upcoming'),
        route('cadence.schedules.index'),
        route('cadence.schedules.show', $schedule),
        route('cadence.schedules.edit', $schedule),
        route('cadence.deliveries.index'),
        route('cadence.deliveries.send'),
        route('admin.users.index'),
        route('admin.roles.index'),
        route('admin.roles.create'),
        route('admin.audit-log.index'),
        route('admin.settings.edit'),
        route('admin.settings.edit'),
        route('profile.edit'),
        route('notifications.index'),
    ];

    // A key that has no translation is echoed verbatim, so any "group.some_key" left in
    // the markup is a missing translation.
    $groups = ['common', 'navigation', 'clients', 'cadence', 'deliveries', 'users', 'roles', 'settings', 'audit', 'profile', 'notifications', 'imports', 'exports', 'enums', 'permissions', 'dashboard', 'auth'];
    $pattern = '/\b('.implode('|', $groups).')\.[a-z0-9_]+(\.[a-z0-9_]+)*\b/';

    // The guest pages are visited first: signing in cannot be undone within one test.
    $guestUrls = [route('login'), route('password.request')];

    foreach ($guestUrls as $url) {
        $content = test()->get($url)->assertOk()->getContent();

        expect(rawTranslationKeysIn((string) $content, $pattern))
            ->toBe([], "Untranslated key(s) rendered on {$url}");
    }

    foreach ($urls as $url) {
        $content = test()->actingAs($administrator)->get($url)->assertOk()->getContent();

        expect(rawTranslationKeysIn((string) $content, $pattern))
            ->toBe([], "Untranslated key(s) rendered on {$url}");
    }
});

/**
 * The dotted keys left in the text a reader actually sees.
 *
 * Markup, scripts and the deliberately shown permission identifiers legitimately
 * contain dotted names, so they are removed before looking.
 *
 * @return list<string>
 */
function rawTranslationKeysIn(string $html, string $pattern): array
{
    $html = preg_replace('/<(svg|script|style)\b.*?<\/\1>/s', '', $html) ?? $html;
    $html = preg_replace('/<span data-permission-identifier\b.*?<\/span>/s', '', $html) ?? $html;
    $html = preg_replace('/\s[\w:@.\-]+="[^"]*"/', '', $html) ?? $html;

    preg_match_all($pattern, $html, $matches);

    return array_values(array_unique($matches[0]));
}
