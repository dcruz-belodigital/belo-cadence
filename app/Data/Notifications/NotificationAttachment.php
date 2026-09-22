<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use App\Models\ClientAttributeFile;

/**
 * One file about to be attached to an outgoing email, already resolved.
 *
 * The mailable prints and attaches; it looks nothing up. By the time one of these exists
 * the binding has been followed through the client's answer to a file row that really
 * belongs to that client, so what is left is where the bytes are and what to call them.
 */
final readonly class NotificationAttachment
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $name,
        public string $mimeType,
        public int $size,
    ) {}

    public static function fromFile(ClientAttributeFile $file): self
    {
        return new self(
            disk: $file->disk,
            path: $file->path,
            name: $file->original_name,
            mimeType: $file->mime_type,
            size: $file->size,
        );
    }

    /**
     * What a delivery records about it: enough for history to say what went out, with no
     * path, because the file it names may since have been replaced or deleted.
     *
     * @return array{name: string, size: int}
     */
    public function toSnapshot(): array
    {
        return ['name' => $this->name, 'size' => $this->size];
    }
}
