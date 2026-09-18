<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * What one slot of an email template is filled from.
 *
 * Either a literal typed for this schedule alone, or a path into one of the client's
 * attributes. The path is a list of repeater field keys walked from the attribute
 * downwards, so `['addresses', 'city']` reaches a field inside a repeater inside a
 * repeater, and an empty path means the attribute itself — including a whole repeater,
 * which the email then renders row by row.
 *
 * The form submits the whole thing as one token (`manual`, or `attribute:7:addresses.city`)
 * so a slot needs one select and one text box rather than a tree widget.
 */
final readonly class TemplateBinding
{
    public const MANUAL = 'manual';

    public const ATTRIBUTE = 'attribute';

    /**
     * @param  list<string>  $path
     */
    private function __construct(
        public string $source,
        public ?int $attributeId = null,
        public array $path = [],
        public ?string $value = null,
    ) {}

    public static function manual(?string $value): self
    {
        return new self(self::MANUAL, value: $value);
    }

    /**
     * @param  list<string>  $path
     */
    public static function attribute(int $attributeId, array $path = []): self
    {
        return new self(self::ATTRIBUTE, attributeId: $attributeId, path: array_values($path));
    }

    /**
     * Reads the token the form submits, or null when it names nothing this application
     * understands.
     */
    public static function fromToken(string $token, ?string $value = null): ?self
    {
        if ($token === self::MANUAL) {
            return self::manual($value);
        }

        $parts = explode(':', $token, 3);

        if (($parts[0] ?? '') !== self::ATTRIBUTE || ! ctype_digit($parts[1] ?? '')) {
            return null;
        }

        $path = ($parts[2] ?? '') === '' ? [] : explode('.', $parts[2]);

        return self::attribute((int) $parts[1], $path);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): ?self
    {
        $source = is_string($raw['source'] ?? null) ? $raw['source'] : '';

        if ($source === self::MANUAL) {
            return self::manual(is_string($raw['value'] ?? null) ? $raw['value'] : null);
        }

        if ($source !== self::ATTRIBUTE || ! is_int($raw['attribute_id'] ?? null)) {
            return null;
        }

        /** @var list<string> $path */
        $path = array_values(array_filter(
            is_array($raw['path'] ?? null) ? $raw['path'] : [],
            static fn (mixed $key): bool => is_string($key),
        ));

        return self::attribute($raw['attribute_id'], $path);
    }

    public function isManual(): bool
    {
        return $this->source === self::MANUAL;
    }

    /**
     * The token this binding would be submitted as, so a saved schedule reopens with its
     * own choice already selected.
     */
    public function token(): string
    {
        if ($this->isManual()) {
            return self::MANUAL;
        }

        if ($this->attributeId === null) {
            throw new InvalidArgumentException('An attribute binding needs an attribute.');
        }

        return self::ATTRIBUTE.':'.$this->attributeId.($this->path === [] ? '' : ':'.implode('.', $this->path));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->isManual()
            ? ['source' => self::MANUAL, 'value' => $this->value]
            : ['source' => self::ATTRIBUTE, 'attribute_id' => $this->attributeId, 'path' => $this->path];
    }
}
