<?php

declare(strict_types=1);

use App\Console\Commands\ProcessDueNotificationsCommand;
use App\Console\Commands\PruneAbandonedImportFilesCommand;
use Illuminate\Support\Facades\Schedule;

/*
| Due client notifications are processed hourly. These are reminders on a monthly or
| yearly cadence, so the hour they go out in is what matters, not the minute; running
| more often only costs a query against an empty result.
|
| Overlapping runs are prevented so two processes can never work on the same occurrence
| at once; the unique index on (schedule, occurrence) is the second line of defence
| behind it.
*/
Schedule::command(ProcessDueNotificationsCommand::class)
    ->hourly()
    ->withoutOverlapping();

/*
| A file uploaded for an import is removed as soon as the import finishes, and when the
| same person starts another one. This clears up after imports that were abandoned.
*/
Schedule::command(PruneAbandonedImportFilesCommand::class)->daily();
