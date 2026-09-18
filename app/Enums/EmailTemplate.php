<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

/**
 * The catalog of email templates the application can send.
 *
 * Templates are source code, never database rows. Adding one means: add a case here,
 * declare its audience, create the matching Blade view, add the translations, and cover
 * it with a test.
 *
 * The blank template is the single exception to copy living in source: it ships no
 * wording of its own and prints the subject and message written on the schedule.
 */
enum EmailTemplate: string
{
    case GeneralReminder = 'general_reminder';
    case MonthlyReminder = 'monthly_reminder';
    case AnnualReminder = 'annual_reminder';
    case StatusUpdate = 'status_update';
    case ActionRequired = 'action_required';
    case Blank = 'blank';

    /**
     * The templates a schedule with this target may choose from, in declaration order.
     *
     * @return list<self>
     */
    public static function for(NotificationTarget $target): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $template): bool => $template->supports($target),
        ));
    }

    public function audience(): EmailTemplateAudience
    {
        return match ($this) {
            self::GeneralReminder, self::MonthlyReminder, self::AnnualReminder => EmailTemplateAudience::Client,
            self::StatusUpdate, self::ActionRequired => EmailTemplateAudience::Recipients,
            self::Blank => EmailTemplateAudience::Any,
        };
    }

    public function supports(NotificationTarget $target): bool
    {
        return $this->audience()->supports($target);
    }

    /**
     * Whether the template brings its own wording.
     *
     * The blank template does not: its subject and message are written on the schedule,
     * so `subject()` has nothing to return and is never asked.
     */
    public function hasOwnCopy(): bool
    {
        return $this !== self::Blank;
    }

    public function label(): string
    {
        return __('enums.email_template.'.$this->value);
    }

    public function view(): string
    {
        return 'mail.notifications.'.str_replace('_', '-', $this->value);
    }

    /**
     * The subject line, optionally in a specific locale so the email is always written
     * in the application's language rather than the reader's.
     *
     * @param  array<string, string>  $replace
     */
    public function subject(array $replace = [], ?string $locale = null): string
    {
        return (string) Lang::get('mail.notifications.'.$this->value.'.subject', $replace, $locale);
    }
}
