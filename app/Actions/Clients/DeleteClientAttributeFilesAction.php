<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\ClientAttributeFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes files, bytes and all.
 *
 * This is the one place a file is destroyed, because it is the one place that can be
 * trusted to destroy both halves of it. Every path that can orphan a file comes through
 * here: saving a client whose answer no longer points at it, and deleting the attribute
 * it was uploaded for.
 *
 * The bytes go only after the surrounding transaction commits. Deleting them first would
 * mean a rollback leaving the row in place pointing at nothing — a broken download is a
 * worse outcome than a file nobody refers to, which `cadence:prune-attribute-files`
 * removes on its own.
 *
 * There is deliberately no model event doing this. An attribute's deletion takes its
 * files by foreign key, which fires no event at all, so an event would only give the
 * appearance of safety.
 */
final class DeleteClientAttributeFilesAction
{
    /**
     * @param  iterable<ClientAttributeFile>  $files
     * @return int How many were deleted.
     */
    public function __invoke(iterable $files): int
    {
        $deleted = 0;

        foreach ($files as $file) {
            [$disk, $path] = [$file->disk, $file->path];

            $file->delete();

            DB::afterCommit(static function () use ($disk, $path): void {
                Storage::disk($disk)->delete($path);
            });

            $deleted++;
        }

        return $deleted;
    }
}
