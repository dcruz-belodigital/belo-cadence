<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Removes files uploaded for an import that was never confirmed.
 *
 * A file is deleted as soon as its import finishes, and when the same person starts
 * another one, so this only ever clears up after imports that were simply abandoned.
 */
final class PruneAbandonedImportFilesCommand extends Command
{
    protected $signature = 'cadence:prune-import-files {--hours=24 : How old an abandoned file must be}';

    protected $description = 'Delete files uploaded for imports that were never confirmed';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = CarbonImmutable::now()->subHours((int) $this->option('hours'))->getTimestamp();
        $deleted = 0;

        foreach ($disk->files('imports') as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->components->info(sprintf('Deleted %d abandoned import file(s).', $deleted));

        return self::SUCCESS;
    }
}
