<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Imports\ImportClientsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\ImportClientsRequest;
use App\Http\Requests\Imports\MapClientImportRequest;
use App\Models\Client;
use App\Support\Csv\CsvReader;
use App\Support\Csv\CsvWriter;
use App\Support\Csv\ImportTemplates;
use App\Support\Csv\PendingImportStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Importing clients happens in two steps: the file is uploaded, then its columns are
 * matched to the ones this application understands. Nothing is written until the matching
 * is confirmed.
 */
final class ClientImportController extends Controller
{
    private const RESOURCE = 'clients';

    private const PREVIEW_ROWS = 5;

    public function create(): View
    {
        $this->authorize('import', Client::class);

        return view('clients.import', ['template' => ImportTemplates::clients()]);
    }

    public function store(ImportClientsRequest $request, PendingImportStore $pending): RedirectResponse
    {
        $this->authorize('import', Client::class);

        $pending->put(self::RESOURCE, $request->file('file'));

        return redirect()->route('clients.import.mapping');
    }

    public function mapping(PendingImportStore $pendingImports, CsvReader $csvReader): View|RedirectResponse
    {
        $this->authorize('import', Client::class);

        $pending = $pendingImports->get(self::RESOURCE);

        if ($pending === null) {
            return redirect()
                ->route('clients.import.create')
                ->with('error', __('imports.errors.expired'));
        }

        $template = ImportTemplates::clients();
        $csv = $csvReader->read($pendingImports->absolutePath($pending));

        return view('clients.import-mapping', [
            'template' => $template,
            'fileName' => $pending->originalName,
            'headings' => $csv->headers,
            'mapping' => $template->suggestMapping($csv->headers),
            'previewRows' => array_slice($csv->rows, 0, self::PREVIEW_ROWS),
            'rowCount' => count($csv->rows),
        ]);
    }

    public function run(
        MapClientImportRequest $request,
        ImportClientsAction $importClients,
        PendingImportStore $pendingImports,
    ): RedirectResponse {
        $this->authorize('import', Client::class);

        $result = $importClients($request->csvPath(), $request->toMapping());

        $pendingImports->forget(self::RESOURCE);

        return redirect()
            ->route('clients.index')
            ->with('success', __('imports.flash.clients', [
                'created' => $result->created,
                'updated' => $result->updated,
            ]));
    }

    public function template(CsvWriter $csvWriter): StreamedResponse
    {
        $this->authorize('import', Client::class);

        return $csvWriter->download(
            ImportTemplates::clients()->toDocument('clients-import-template.csv'),
        );
    }
}
