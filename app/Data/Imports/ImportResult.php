<?php

declare(strict_types=1);

namespace App\Data\Imports;

/**
 * What an import actually did, so the outcome can be reported instead of guessed.
 */
final readonly class ImportResult
{
    public function __construct(
        public int $created,
        public int $updated,
    ) {}
}
