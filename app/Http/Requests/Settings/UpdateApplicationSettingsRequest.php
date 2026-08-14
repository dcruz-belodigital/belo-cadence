<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Data\Settings\ApplicationSettingsData;
use App\Enums\Locale;
use App\Enums\Theme;
use App\Rules\EmailAddressRule;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\TimezoneIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateApplicationSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_name' => ['required', 'string', 'max:255'],
            'default_locale' => ['required', Rule::enum(Locale::class)],
            'default_timezone' => ['required', 'string', 'timezone'],
            'default_theme' => ['required', Rule::enum(Theme::class)],
            'client_email_sender_name' => ['required', 'string', 'max:255'],
            'client_email_sender_email' => ['required', 'string', 'max:255', new EmailAddressRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'application_name' => __('settings.fields.application_name'),
            'default_locale' => __('settings.fields.default_locale'),
            'default_timezone' => __('settings.fields.default_timezone'),
            'default_theme' => __('settings.fields.default_theme'),
            'client_email_sender_name' => __('settings.fields.client_email_sender_name'),
            'client_email_sender_email' => __('settings.fields.client_email_sender_email'),
        ];
    }

    public function toData(): ApplicationSettingsData
    {
        return new ApplicationSettingsData(
            applicationName: $this->string('application_name')->toString(),
            defaultLocale: Locale::from($this->string('default_locale')->toString()),
            defaultTimezone: new TimezoneIdentifier($this->string('default_timezone')->toString()),
            defaultTheme: Theme::from($this->string('default_theme')->toString()),
            clientEmailSenderName: $this->string('client_email_sender_name')->toString(),
            clientEmailSenderEmail: new EmailAddress($this->string('client_email_sender_email')->toString()),
        );
    }
}
