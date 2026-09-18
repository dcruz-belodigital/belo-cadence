<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Exports\ExportNotificationDeliveriesAction;
use App\Data\Notifications\NotificationDeliveryFilters;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Support\Csv\CsvWriter;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NotificationDeliveryExportController extends Controller
{
    public function __invoke(
        Request $request,
        ViewerTimezone $viewerTimezone,
        ExportNotificationDeliveriesAction $exportDeliveries,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', NotificationDelivery::class);

        $document = $exportDeliveries(
            NotificationDeliveryFilters::fromRequest($request, $viewerTimezone->current()),
            ExportMode::fromRequestValue($request->string('mode')->toString()),
        );

        return $csvWriter->download($document);
    }
}
