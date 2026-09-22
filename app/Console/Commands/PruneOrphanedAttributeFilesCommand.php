<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ClientAttributeFile;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Removes uploaded attribute files that no row claims any more.
 *
 * A file is deleted along with its row the moment nothing points at it, so this only
 * ever finds the one case that cannot be handled there: an upload written to the disk
 * inside a transaction that then rolled back, leaving bytes whose row was never
 * committed. Writing the bytes outside the transaction instead would trade this for a
 * worse failure — a committed row pointing at a file that was never written.
 *
 * The age cutoff is what makes it safe to run while somebody is saving a client: an
 * upload is only a candidate once it is far too old to be part of a request still in
 * flight.
 */
final class PruneOrphanedAttributeFilesCommand extends Command
{
    protected $signature = 'cadence:prune-attribute-files {--hours=24 : How old an unclaimed file must be}';

    protected $description = 'Delete uploaded client attribute files that no record refers to';

    public function handle(): int
    {
        $disk = Storage::disk(ClientAttributeFile::DISK);
        $cutoff = CarbonImmutable::now()->subHours((int) $this->option('hours'))->getTimestamp();

        $claimed = ClientAttributeFile::query()->pluck('path')->flip();
        $deleted = 0;

        foreach ($disk->files(ClientAttributeFile::DIRECTORY) as $path) {
            if ($claimed->has($path) || $disk->lastModified($path) >= $cutoff) {
                continue;
            }

            $disk->delete($path);
            $deleted++;
        }

        $this->components->info(sprintf('Deleted %d unclaimed attribute file(s).', $deleted));

        return self::SUCCESS;
    }
}
