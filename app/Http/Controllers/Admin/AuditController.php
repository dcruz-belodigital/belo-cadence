<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Data\Audits\AuditFilters;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Support\ViewerTimezone;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The audit log can be read, filtered and exported. It is never written to from here.
 */
final class AuditController extends Controller
{
    public function index(Request $request, ViewerTimezone $viewerTimezone): View
    {
        $this->authorize('viewAny', Audit::class);

        $filters = AuditFilters::fromRequest($request, $viewerTimezone->current());

        return view('admin.audit.index', [
            'filters' => $filters,
            'audits' => Audit::query()
                ->filtered($filters)
                ->with('user')
                ->paginate(25)
                ->withQueryString(),
            'auditableTypes' => Audit::query()
                ->whereNotNull('auditable_type')
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type'),
        ]);
    }

    public function show(Audit $audit): View
    {
        $this->authorize('view', $audit);

        $audit->load('user');

        return view('admin.audit.show', ['audit' => $audit]);
    }
}
