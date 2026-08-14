<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The two kinds of CSV export the application offers.
 *
 * Raw exports are machine-oriented: stable column names, raw enum values and ISO
 * timestamps. Table exports mirror what the reader currently sees on screen,
 * including their search, filters and sorting.
 */
enum ExportMode: string
{
    case Raw = 'raw';
    case Table = 'table';

    public static function fromRequestValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Table;
    }
}
