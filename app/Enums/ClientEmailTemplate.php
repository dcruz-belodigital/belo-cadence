<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

/**
 * The catalog of client email templates the application can send.
 *
 * Templates are source code, never database rows. Adding one means: add a case here,
 * create the matching Blade view, add the translations, and cover it with a test.
 */
enum ClientEmailTemplate: string
{
    case GeneralReminder = 'general_reminder';
    case MonthlyReminder = 'monthly_reminder';
    case AnnualReminder = 'annual_reminder';

    public function label(): string
    {
        return __('enums.client_email_template.'.$this->value);
    }

    public function view(): string
    {
        return 'mail.client-notifications.'.str_replace('_', '-', $this->value);
    }

    /**
     * The subject line, optionally in a specific locale so a client email is always
     * written in the application's language rather than the reader's.
     *
     * @param  array<string, string>  $replace
     */
    public function subject(array $replace = [], ?string $locale = null): string
    {
        return (string) Lang::get('mail.client_notifications.'.$this->value.'.subject', $replace, $locale);
    }
}
