<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Notifications\ProcessDueNotificationsAction;
use Illuminate\Console\Command;

/**
 * The scheduler's entry point into sending. All behaviour lives in the action.
 */
final class ProcessDueNotificationsCommand extends Command
{
    protected $signature = 'cadence:process-notifications';

    protected $description = 'Send the notifications whose scheduled occurrence has arrived';

    public function handle(ProcessDueNotificationsAction $processDueNotifications): int
    {
        $result = $processDueNotifications();

        $this->components->info(sprintf(
            'Processed %d due notification(s): %d sent, %d failed, %d already handled, %d skipped.',
            $result->total(),
            $result->sent,
            $result->failed,
            $result->alreadyHandled,
            $result->skipped,
        ));

        return self::SUCCESS;
    }
}
