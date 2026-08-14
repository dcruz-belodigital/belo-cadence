<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Exports\ExportClientsAction;
use App\Data\Clients\ClientFilters;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Support\Csv\CsvWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClientExportController extends Controller
{
    public function __invoke(
        Request $request,
        ExportClientsAction $exportClients,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', Client::class);

        $document = $exportClients(
            ClientFilters::fromRequest($request),
            ExportMode::fromRequestValue($request->string('mode')->toString()),
        );

        return $csvWriter->download($document);
    }
}
