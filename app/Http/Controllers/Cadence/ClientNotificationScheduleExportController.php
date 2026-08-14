<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Exports\ExportClientNotificationSchedulesAction;
use App\Data\ClientNotifications\ClientNotificationScheduleFilters;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\ClientNotificationSchedule;
use App\Support\Csv\CsvWriter;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClientNotificationScheduleExportController extends Controller
{
    public function __invoke(
        Request $request,
        ViewerTimezone $viewerTimezone,
        ExportClientNotificationSchedulesAction $exportSchedules,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', ClientNotificationSchedule::class);

        $document = $exportSchedules(
            ClientNotificationScheduleFilters::fromRequest(
                $request,
                $viewerTimezone->current(),
                scheduledOnly: $request->boolean('upcoming'),
            ),
            ExportMode::fromRequestValue($request->string('mode')->toString()),
        );

        return $csvWriter->download($document);
    }
}
