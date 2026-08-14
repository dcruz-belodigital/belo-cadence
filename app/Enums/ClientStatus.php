<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __('enums.client_status.'.$this->value);
    }

    public function badgeVariant(): BadgeVariant
    {
        return match ($this) {
            self::Active => BadgeVariant::Success,
            self::Inactive => BadgeVariant::Neutral,
        };
    }
}
