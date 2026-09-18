<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * The options a select attribute offers, in the order they were written.
 *
 * Retired choices are kept. Deleting one outright would make `Rule::in` reject every
 * client still holding it, so editing such a client's *name* would fail on a field the
 * person never touched — and rendering the stale value as a disabled option is worse,
 * because the select falls back to its first option and silently rewrites the data.
 */
final readonly class ClientAttributeChoices
{
    /**
     * @param  list<ClientAttributeChoice>  $choices
     */
    public function __construct(
        public array $choices = [],
    ) {}

    /**
     * @param  array<int, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $choices = [];

        foreach ($raw as $choice) {
            if (is_string($choice)) {
                $choices[] = new ClientAttributeChoice($choice);

                continue;
            }

            if (is_array($choice) && is_string($choice['value'] ?? null)) {
                $choices[] = new ClientAttributeChoice(
                    $choice['value'],
                    (bool) ($choice['is_active'] ?? true),
                );
            }
        }

        return new self($choices);
    }

    /**
     * The choices as the definition form submits them: one per line, active ones only.
     *
     * Anything already stored that is no longer listed is retired rather than dropped,
     * and retyping a retired option brings it back.
     */
    public function mergeLines(string $text): self
    {
        $submitted = self::splitLines($text);

        $merged = array_map(
            static fn (string $value): ClientAttributeChoice => new ClientAttributeChoice($value),
            $submitted,
        );

        foreach ($this->choices as $existing) {
            if (! in_array($existing->value, $submitted, true)) {
                $merged[] = new ClientAttributeChoice($existing->value, isActive: false);
            }
        }

        return new self($merged);
    }

    /**
     * One option per line.
     *
     * Splitting on line breaks alone, rather than reusing the recipient splitter, is
     * deliberate: that one also splits on spaces, which would turn "In progress" into
     * two options.
     *
     * @return list<string>
     */
    public static function splitLines(string $text): array
    {
        $lines = preg_split('/\R/', trim($text)) ?: [];

        $values = [];

        foreach ($lines as $line) {
            $value = trim($line);

            if ($value !== '' && ! in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @return list<ClientAttributeChoice>
     */
    public function active(): array
    {
        return array_values(array_filter(
            $this->choices,
            static fn (ClientAttributeChoice $choice): bool => $choice->isActive,
        ));
    }

    /**
     * Every value a client may hold, retired ones included — this is what validates.
     *
     * @return list<string>
     */
    public function values(): array
    {
        return array_map(static fn (ClientAttributeChoice $choice): string => $choice->value, $this->choices);
    }

    /**
     * @return list<string>
     */
    public function activeValues(): array
    {
        return array_map(static fn (ClientAttributeChoice $choice): string => $choice->value, $this->active());
    }

    public function isRetired(string $value): bool
    {
        foreach ($this->choices as $choice) {
            if ($choice->value === $value) {
                return ! $choice->isActive;
            }
        }

        return false;
    }

    /**
     * What the definition form shows in its textarea.
     */
    public function toLines(): string
    {
        return implode("\n", $this->activeValues());
    }

    public function isEmpty(): bool
    {
        return $this->active() === [];
    }

    /**
     * @return list<array{value: string, is_active: bool}>
     */
    public function toArray(): array
    {
        return array_map(static fn (ClientAttributeChoice $choice): array => [
            'value' => $choice->value,
            'is_active' => $choice->isActive,
        ], $this->choices);
    }
}
