<?php

declare(strict_types=1);

namespace App\Data\Imports;

/**
 * A file somebody uploaded and has not confirmed yet.
 */
final readonly class PendingImport
{
    public function __construct(
        public string $storedPath,
        public string $originalName,
    ) {}
}
