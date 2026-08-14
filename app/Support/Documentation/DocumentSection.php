<?php

declare(strict_types=1);

namespace App\Support\Documentation;

/**
 * One `##` section of a markdown document, already rendered.
 */
final readonly class DocumentSection
{
    public function __construct(
        public string $id,
        public string $heading,
        public string $html,
    ) {}
}
