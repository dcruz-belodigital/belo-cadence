<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The visual variants the badge component supports.
 *
 * Domain enums map their own states onto these so status colours stay consistent
 * across every table, detail page and dashboard panel.
 */
enum BadgeVariant: string
{
    case Neutral = 'neutral';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
    case Info = 'info';
}
