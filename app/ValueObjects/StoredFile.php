<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * How an answer refers to a file that has been uploaded.
 *
 * The bytes belong to a `client_attribute_files` row; this is what is written into the
 * answer itself. It carries the name and the size as well as the id so that a page, a
 * CSV cell or an email can read an answer without a second lookup — the two never drift,
 * because a file is never renamed and a replacement is a new row.
 *
 * The id is the only part anything trusts. A download resolves it against the file table
 * and checks who that row belongs to, so an answer edited by hand can point at nothing
 * more than it was already allowed to see.
 */
final readonly class StoredFile
{
    public function __construct(
        public int $id,
        public string $name,
        public int $size,
    ) {}

    /**
     * Reads the reference out of a stored answer, or null when the answer is not one.
     */
    public static function fromValue(mixed $value): ?self
    {
        if (! is_array($value) || ! isset($value['id']) || ! is_numeric($value['id'])) {
            return null;
        }

        return new self(
            id: (int) $value['id'],
            name: is_string($value['name'] ?? null) ? $value['name'] : '',
            size: is_numeric($value['size'] ?? null) ? (int) $value['size'] : 0,
        );
    }

    /**
     * Every file referred to anywhere in one answer, however deeply it nests.
     *
     * A file can sit at the top of an answer or in a cell of a repeating row, so this
     * walks whatever shape it is given rather than being told where to look.
     *
     * @return list<self>
     */
    public static function allIn(mixed $value): array
    {
        $reference = self::fromValue($value);

        if ($reference instanceof self) {
            return [$reference];
        }

        if (! is_array($value)) {
            return [];
        }

        $found = [];

        foreach ($value as $entry) {
            $found = [...$found, ...self::allIn($entry)];
        }

        return $found;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'size' => $this->size,
        ];
    }
}
