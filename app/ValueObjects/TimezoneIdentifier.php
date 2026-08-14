<?php

declare(strict_types=1);

namespace App\ValueObjects;

use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An IANA timezone identifier, for example "Europe/Lisbon".
 */
final readonly class TimezoneIdentifier implements JsonSerializable, Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if (! in_array($normalized, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException("[{$value}] is not a valid timezone identifier.");
        }

        $this->value = $normalized;
    }

    public static function utc(): self
    {
        return new self('UTC');
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    public function toDateTimeZone(): DateTimeZone
    {
        return new DateTimeZone($this->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
