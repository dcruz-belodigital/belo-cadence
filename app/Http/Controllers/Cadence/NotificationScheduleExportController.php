<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Exports\ExportNotificationSchedulesAction;
use App\Data\Notifications\NotificationScheduleFilters;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\NotificationSchedule;
use App\Support\Csv\CsvWriter;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NotificationScheduleExportController extends Controller
{
    public function __invoke(
        Request $request,
        ViewerTimezone $viewerTimezone,
        ExportNotificationSchedulesAction $exportSchedules,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', NotificationSchedule::class);

        $document = $exportSchedules(
            NotificationScheduleFilters::fromRequest(
                $request,
                $viewerTimezone->current(),
                scheduledOnly: $request->boolean('upcoming'),
            ),
            ExportMode::fromRequestValue($request->string('mode')->toString()),
        );

        return $csvWriter->download($document);
    }
}
