<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Settings\ApplicationSettingsData;
use App\Enums\AuditAction;
use App\Models\ApplicationSettings;
use Illuminate\Support\Facades\DB;

/**
 * Stores the application-wide settings.
 *
 * Changing the sender here affects future emails only: every delivery already recorded
 * keeps the sender it was actually sent with.
 */
final class UpdateApplicationSettingsAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(ApplicationSettingsData $data): ApplicationSettings
    {
        return DB::transaction(function () use ($data): ApplicationSettings {
            $settings = ApplicationSettings::query()->orderBy('id')->first() ?? new ApplicationSettings;

            $before = $settings->exists ? $this->auditValues($settings) : null;

            $settings->fill([
                'application_name' => $data->applicationName,
                'default_locale' => $data->defaultLocale,
                'default_timezone' => $data->defaultTimezone,
                'default_theme' => $data->defaultTheme,
                'client_email_sender_name' => $data->clientEmailSenderName,
                'client_email_sender_email' => $data->clientEmailSenderEmail,
            ]);

            $settings->save();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ApplicationSettingsUpdated,
                auditable: $settings,
                oldValues: $before,
                newValues: $this->auditValues($settings),
            ));

            return $settings;
        });
    }

    /**
     * @return array<string, string>
     */
    private function auditValues(ApplicationSettings $settings): array
    {
        return [
            'application_name' => $settings->application_name,
            'default_locale' => $settings->default_locale->value,
            'default_timezone' => $settings->default_timezone->value,
            'default_theme' => $settings->default_theme->value,
            'client_email_sender_name' => $settings->client_email_sender_name,
            'client_email_sender_email' => $settings->client_email_sender_email->value,
        ];
    }
}
