<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidCsvException extends RuntimeException
{
    public static function unreadable(): self
    {
        return new self(__('imports.errors.unreadable'));
    }

    public static function missingHeaderRow(): self
    {
        return new self(__('imports.errors.missing_header_row'));
    }
}
