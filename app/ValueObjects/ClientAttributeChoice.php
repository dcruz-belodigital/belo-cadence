<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * One option a select attribute offers.
 *
 * The stored value is the text itself, so a CSV cell reads the way the option does and
 * nothing has to be translated back. A choice is never removed outright — it is retired
 * (`isActive = false`), which keeps every client already holding it valid. See
 * `ClientAttributeChoices::mergeLines()`.
 */
final readonly class ClientAttributeChoice implements Stringable
{
    public string $value;

    public function __construct(string $value, public bool $isActive = true)
    {
        $normalised = trim($value);

        if ($normalised === '') {
            throw new InvalidArgumentException('A choice cannot be blank.');
        }

        $this->value = $normalised;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
