<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ClientNotifications\ProcessDueClientNotificationsAction;
use Illuminate\Console\Command;

/**
 * The scheduler's entry point into sending. All behaviour lives in the action.
 */
final class ProcessDueClientNotificationsCommand extends Command
{
    protected $signature = 'cadence:process-notifications';

    protected $description = 'Send the client notifications whose scheduled occurrence has arrived';

    public function handle(ProcessDueClientNotificationsAction $processDueNotifications): int
    {
        $result = $processDueNotifications();

        $this->components->info(sprintf(
            'Processed %d due notification(s): %d sent, %d failed, %d already handled.',
            $result->total(),
            $result->sent,
            $result->failed,
            $result->alreadyHandled,
        ));

        return self::SUCCESS;
    }
}
