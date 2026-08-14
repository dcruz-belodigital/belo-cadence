<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Exports\ExportUsersAction;
use App\Data\Users\UserFilters;
use App\Enums\ExportMode;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Csv\CsvWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserExportController extends Controller
{
    public function __invoke(
        Request $request,
        ExportUsersAction $exportUsers,
        CsvWriter $csvWriter,
    ): StreamedResponse {
        $this->authorize('export', User::class);

        $document = $exportUsers(
            UserFilters::fromRequest($request),
            ExportMode::fromRequestValue($request->string('mode')->toString()),
        );

        return $csvWriter->download($document);
    }
}
