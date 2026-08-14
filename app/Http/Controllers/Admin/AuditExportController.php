<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Exports\ExportAuditsAction;
use App\Data\Audits\AuditFilters;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Support\Csv\CsvWriter;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditExportController extends Controller
{
    public function __invoke(
        Request $request,
        ViewerTimezone $viewerTimezone,
        ExportAuditsAction $exportAudits,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', Audit::class);

        $document = $exportAudits(
            AuditFilters::fromRequest($request, $viewerTimezone->current()),
            ExportMode::fromRequestValue($request->string('mode')->toString()),
        );

        return $csvWriter->download($document);
    }
}
