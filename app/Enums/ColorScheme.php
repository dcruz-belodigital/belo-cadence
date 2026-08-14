<?php

declare(strict_types=1);

namespace App\Enums;

enum ColorScheme: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public function label(): string
    {
        return __('enums.color_scheme.'.$this->value);
    }
}
