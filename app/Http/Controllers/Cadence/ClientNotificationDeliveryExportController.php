<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cadence;

use App\Actions\Exports\ExportClientNotificationDeliveriesAction;
use App\Data\ClientNotifications\ClientNotificationDeliveryFilters;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\ClientNotificationDelivery;
use App\Support\Csv\CsvWriter;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClientNotificationDeliveryExportController extends Controller
{
    public function __invoke(
        Request $request,
        ViewerTimezone $viewerTimezone,
        ExportClientNotificationDeliveriesAction $exportDeliveries,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', ClientNotificationDelivery::class);

        $document = $exportDeliveries(
            ClientNotificationDeliveryFilters::fromRequest($request, $viewerTimezone->current()),
            ExportMode::fromRequestValue($request->string('mode')->toString()),
        );

        return $csvWriter->download($document);
    }
}
