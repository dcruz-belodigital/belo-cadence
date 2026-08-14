<?php

declare(strict_types=1);

use App\Actions\ClientNotifications\ProcessDueClientNotificationsAction;
use App\Enums\AuditAction;
use App\Enums\ClientEmailTemplate;
use App\Enums\ClientNotificationFrequency;
use App\Enums\PermissionName;
use App\Mail\ClientNotificationMail;
use App\Models\ApplicationSettings;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientNotificationSchedule;
use App\Models\DefaultClientNotification;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function settingsForm(array $overrides = []): array
{
    return array_merge([
        'application_name' => 'Belo Cadence',
        'default_locale' => 'en',
        'default_timezone' => 'UTC',
        'default_theme' => 'iris',
        'client_email_sender_name' => 'Belo Cadence',
        'client_email_sender_email' => 'cadence@belo-cadence.test',
    ], $overrides);
}

describe('application settings', function (): void {
    it('falls back to the configured defaults before anything is stored', function (): void {
        expect(ApplicationSettings::query()->count())->toBe(0)
            ->and(ApplicationSettings::current()->application_name)->toBe(config('app.name'));
    });

    it('stores the settings and records the change', function (): void {
        actingAs(administrator())
            ->put(route('admin.settings.update'), settingsForm([
                'application_name' => 'Cadence for Belo',
                'default_timezone' => 'Europe/Lisbon',
                'client_email_sender_name' => 'The Belo Team',
                'client_email_sender_email' => 'Team@Belo.test',
            ]))
            ->assertRedirect(route('admin.settings.edit'));

        $settings = ApplicationSettings::current();

        expect($settings->application_name)->toBe('Cadence for Belo')
            ->and($settings->default_timezone->value)->toBe('Europe/Lisbon')
            ->and($settings->client_email_sender_name)->toBe('The Belo Team')
            ->and($settings->client_email_sender_email->value)->toBe('team@belo.test');

        assertDatabaseHas('audits', [
            'action' => AuditAction::ApplicationSettingsUpdated->value,
            'auditable_type' => 'application_settings',
        ]);
    });

    it('records what changed', function (): void {
        actingAs(administrator())->put(route('admin.settings.update'), settingsForm());
        actingAs(administrator())->put(route('admin.settings.update'), settingsForm(['application_name' => 'Renamed']));

        $audit = Audit::query()->where('action', AuditAction::ApplicationSettingsUpdated->value)->latest('id')->firstOrFail();

        expect($audit->old_values['application_name'])->toBe('Belo Cadence')
            ->and($audit->new_values['application_name'])->toBe('Renamed');
    });

    it('validates the submitted settings', function (array $overrides, string $field): void {
        actingAs(administrator())
            ->put(route('admin.settings.update'), settingsForm($overrides))
            ->assertSessionHasErrors($field);
    })->with([
        'empty name' => [['application_name' => ''], 'application_name'],
        'unknown locale' => [['default_locale' => 'martian'], 'default_locale'],
        'unknown timezone' => [['default_timezone' => 'Mars/Olympus'], 'default_timezone'],
        'unknown theme' => [['default_theme' => 'vantablack'], 'default_theme'],
        'invalid sender address' => [['client_email_sender_email' => 'not-an-email'], 'client_email_sender_email'],
    ]);

    it('shows the application name in the interface', function (): void {
        actingAs(administrator())->put(route('admin.settings.update'), settingsForm(['application_name' => 'Cadence for Belo']));

        actingAs(administrator())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cadence for Belo');
    });

    it('uses the stored sender for client email from then on', function (): void {
        Mail::fake();

        actingAs(administrator())->put(route('admin.settings.update'), settingsForm([
            'client_email_sender_name' => 'The Belo Team',
            'client_email_sender_email' => 'team@belo.test',
        ]));

        ClientNotificationSchedule::factory()->for(Client::factory())->due()->create();

        app(ProcessDueClientNotificationsAction::class)();

        Mail::assertSent(ClientNotificationMail::class, fn (ClientNotificationMail $mail): bool => $mail->hasFrom('team@belo.test', 'The Belo Team'));
    });

    it('does not expose mail server credentials', function (): void {
        actingAs(administrator())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertDontSee('MAIL_PASSWORD')
            ->assertDontSee('name="mail_password"', escape: false)
            ->assertSee(__('settings.hints.infrastructure'));
    });

    it('refuses a user who may only view the settings', function (): void {
        actingAs(administratorWithout([PermissionName::ApplicationSettingsUpdate]))
            ->put(route('admin.settings.update'), settingsForm())
            ->assertForbidden();
    });
});

describe('default client notifications', function (): void {
    it('stores the offered defaults and records the change', function (): void {
        actingAs(administrator())
            ->put(route('admin.default-client-notifications.update'), [
                'entries' => [
                    ['template' => ClientEmailTemplate::MonthlyReminder->value, 'frequency' => ClientNotificationFrequency::Monthly->value, 'is_enabled_by_default' => '1'],
                    ['template' => ClientEmailTemplate::AnnualReminder->value, 'frequency' => ClientNotificationFrequency::Yearly->value, 'is_enabled_by_default' => '0'],
                ],
            ])
            ->assertRedirect(route('admin.settings.edit'));

        expect(DefaultClientNotification::query()->count())->toBe(2);

        $monthly = DefaultClientNotification::query()->where('template', ClientEmailTemplate::MonthlyReminder)->firstOrFail();

        expect($monthly->frequency)->toBe(ClientNotificationFrequency::Monthly)
            ->and($monthly->is_enabled_by_default)->toBeTrue();

        assertDatabaseHas('audits', ['action' => AuditAction::DefaultClientNotificationsUpdated->value]);
    });

    it('removes entries that are left out', function (): void {
        DefaultClientNotification::factory()
            ->forTemplate(ClientEmailTemplate::GeneralReminder, ClientNotificationFrequency::OneTime)
            ->create();

        actingAs(administrator())
            ->put(route('admin.default-client-notifications.update'), ['entries' => []])
            ->assertRedirect();

        expect(DefaultClientNotification::query()->count())->toBe(0);
    });

    it('keeps only one entry per template and frequency', function (): void {
        actingAs(administrator())
            ->put(route('admin.default-client-notifications.update'), [
                'entries' => [
                    ['template' => ClientEmailTemplate::MonthlyReminder->value, 'frequency' => ClientNotificationFrequency::Monthly->value, 'is_enabled_by_default' => '1'],
                    ['template' => ClientEmailTemplate::MonthlyReminder->value, 'frequency' => ClientNotificationFrequency::Monthly->value, 'is_enabled_by_default' => '0'],
                ],
            ])
            ->assertRedirect();

        expect(DefaultClientNotification::query()->count())->toBe(1);
    });

    it('rejects a template the application does not know', function (): void {
        actingAs(administrator())
            ->put(route('admin.default-client-notifications.update'), [
                'entries' => [
                    ['template' => 'weekly_digest', 'frequency' => ClientNotificationFrequency::Monthly->value],
                ],
            ])
            ->assertSessionHasErrors('entries.0.template');
    });

    it('offers the configured defaults on the client creation form', function (): void {
        DefaultClientNotification::factory()
            ->forTemplate(ClientEmailTemplate::AnnualReminder, ClientNotificationFrequency::Yearly)
            ->create();

        actingAs(administrator())
            ->get(route('clients.create'))
            ->assertOk()
            ->assertSee(ClientEmailTemplate::AnnualReminder->label())
            ->assertSee(__('clients.create.apply'));
    });

    it('explains that new clients start without schedules when there are no defaults', function (): void {
        actingAs(administrator())
            ->get(route('clients.create'))
            ->assertOk()
            ->assertSee(__('clients.create.defaults_empty'));
    });

    it('refuses a user who may only view the defaults', function (): void {
        actingAs(administratorWithout([PermissionName::DefaultClientNotificationsUpdate]))
            ->put(route('admin.default-client-notifications.update'), ['entries' => []])
            ->assertForbidden();
    });
});
