<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Exports\ExportRolesAction;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Csv\CsvWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RoleExportController extends Controller
{
    public function __invoke(
        Request $request,
        ExportRolesAction $exportRoles,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', Role::class);

        return $csvWriter->download(
            $exportRoles(ExportMode::fromRequestValue($request->string('mode')->toString())),
        );
    }
}
