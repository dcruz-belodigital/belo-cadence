<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Every slot of a schedule's template, and what fills it, keyed by slot.
 */
final readonly class TemplateBindings
{
    /**
     * @param  array<string, TemplateBinding>  $bindings
     */
    public function __construct(
        public array $bindings = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $bindings = [];

        foreach ($raw as $slot => $binding) {
            if (! is_string($slot) || ! is_array($binding)) {
                continue;
            }

            $parsed = TemplateBinding::fromArray($binding);

            if ($parsed instanceof TemplateBinding) {
                $bindings[$slot] = $parsed;
            }
        }

        return new self($bindings);
    }

    public function for(string $slot): ?TemplateBinding
    {
        return $this->bindings[$slot] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->bindings === [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (TemplateBinding $binding): array => $binding->toArray(), $this->bindings);
    }
}
