<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class MissingDocumentException extends RuntimeException
{
    public static function at(string $path): self
    {
        return new self("The document [{$path}] does not exist.");
    }
}
