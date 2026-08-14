<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Imports\ImportUsersAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Imports\MapUserImportRequest;
use App\Http\Requests\Users\ImportUsersRequest;
use App\Models\User;
use App\Support\Csv\CsvReader;
use App\Support\Csv\CsvWriter;
use App\Support\Csv\ImportTemplates;
use App\Support\Csv\PendingImportStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Importing users happens in two steps: the file is uploaded, then its columns are matched
 * to the ones this application understands. Nothing is written until the matching is
 * confirmed.
 */
final class UserImportController extends Controller
{
    private const RESOURCE = 'users';

    private const PREVIEW_ROWS = 5;

    public function create(): View
    {
        $this->authorize('import', User::class);

        return view('admin.users.import', ['template' => ImportTemplates::users()]);
    }

    public function store(ImportUsersRequest $request, PendingImportStore $pending): RedirectResponse
    {
        $this->authorize('import', User::class);

        $pending->put(self::RESOURCE, $request->file('file'));

        return redirect()->route('admin.users.import.mapping');
    }

    public function mapping(PendingImportStore $pendingImports, CsvReader $csvReader): View|RedirectResponse
    {
        $this->authorize('import', User::class);

        $pending = $pendingImports->get(self::RESOURCE);

        if ($pending === null) {
            return redirect()
                ->route('admin.users.import.create')
                ->with('error', __('imports.errors.expired'));
        }

        $template = ImportTemplates::users();
        $csv = $csvReader->read($pendingImports->absolutePath($pending));

        return view('admin.users.import-mapping', [
            'template' => $template,
            'fileName' => $pending->originalName,
            'headings' => $csv->headers,
            'mapping' => $template->suggestMapping($csv->headers),
            'previewRows' => array_slice($csv->rows, 0, self::PREVIEW_ROWS),
            'rowCount' => count($csv->rows),
        ]);
    }

    public function run(
        MapUserImportRequest $request,
        ImportUsersAction $importUsers,
        PendingImportStore $pendingImports,
    ): RedirectResponse {
        $this->authorize('import', User::class);

        $result = $importUsers($request->csvPath(), $request->toMapping());

        $pendingImports->forget(self::RESOURCE);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('imports.flash.users', [
                'created' => $result->created,
                'updated' => $result->updated,
            ]));
    }

    public function template(CsvWriter $csvWriter): StreamedResponse
    {
        $this->authorize('import', User::class);

        return $csvWriter->download(
            ImportTemplates::users()->toDocument('users-import-template.csv'),
        );
    }
}
