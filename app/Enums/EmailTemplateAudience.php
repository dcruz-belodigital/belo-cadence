<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who a template is written for.
 *
 * A template's wording assumes something about its reader: a client email speaks to the
 * client it is about, while a recipient-list email speaks to whoever was put on the
 * list. The audience is what stops a schedule offering a template that would read
 * wrongly to the people receiving it.
 */
enum EmailTemplateAudience: string
{
    case Client = 'client';
    case Recipients = 'recipients';
    case Any = 'any';

    public function supports(NotificationTarget $target): bool
    {
        return match ($this) {
            self::Any => true,
            self::Client => $target === NotificationTarget::Client,
            self::Recipients => $target === NotificationTarget::Recipients,
        };
    }

    public function label(): string
    {
        return __('enums.email_template_audience.'.$this->value);
    }
}
