<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * The client attributes whose files a notification attaches.
 *
 * Unlike a template value, an attachment fills no blank in any wording, so it belongs to
 * the schedule rather than to the template: any notification about a client may carry
 * any of that client's files, and adding one is a choice on a form rather than a change
 * to source.
 *
 * Each entry is an ordinary `TemplateBinding`, so "the Signed contract attribute" and
 * "the Document field of the Certificates rows" are the same kind of thing — and the
 * second attaches every row's file, which is how one choice becomes several attachments.
 * A literal is meaningless here: there is no way to type a file.
 */
final readonly class TemplateAttachments
{
    /**
     * How many attributes one notification may draw its attachments from.
     *
     * A cap rather than a limit on the files themselves: one repeating attribute can
     * still hold several, and what an email may weigh is the mail server's answer.
     */
    public const MAX = 10;

    /**
     * @param  list<TemplateBinding>  $bindings
     */
    public function __construct(
        public array $bindings = [],
    ) {}

    /**
     * Reads the tokens the form submits, dropping anything that names nothing.
     *
     * @param  array<int, mixed>  $tokens
     */
    public static function fromTokens(array $tokens): self
    {
        $bindings = [];

        foreach (array_unique(array_filter($tokens, 'is_string')) as $token) {
            $binding = TemplateBinding::fromToken($token);

            // A literal cannot produce a file, so only an attribute is an attachment.
            if ($binding instanceof TemplateBinding && ! $binding->isManual()) {
                $bindings[] = $binding;
            }
        }

        return new self($bindings);
    }

    /**
     * @param  array<int, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        $bindings = [];

        foreach ($raw as $entry) {
            $binding = is_array($entry) ? TemplateBinding::fromArray($entry) : null;

            if ($binding instanceof TemplateBinding && ! $binding->isManual()) {
                $bindings[] = $binding;
            }
        }

        return new self($bindings);
    }

    /**
     * The tokens these bindings were submitted as, so a saved schedule reopens with its
     * own choices ticked.
     *
     * @return list<string>
     */
    public function tokens(): array
    {
        return array_map(static fn (TemplateBinding $binding): string => $binding->token(), $this->bindings);
    }

    public function isEmpty(): bool
    {
        return $this->bindings === [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (TemplateBinding $binding): array => $binding->toArray(), $this->bindings);
    }
}
