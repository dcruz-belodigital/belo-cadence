<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateApplicationSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateApplicationSettingsRequest;
use App\Models\ApplicationSettings;
use App\Models\DefaultClientNotification;
use App\Support\ClientEmailTemplatePreview;
use App\ValueObjects\TimezoneIdentifier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The settings page, which gathers everything an administrator configures once.
 *
 * The default notifications live here as a section rather than as a page of their own,
 * but they keep their own permission, their own form and their own audit entry — so the
 * page shows only the sections the reader may actually see, and someone who may edit
 * one of them is not handed the other.
 */
final class ApplicationSettingsController extends Controller
{
    public function edit(ClientEmailTemplatePreview $templatePreviews): View
    {
        $mayViewSettings = Gate::allows('view', ApplicationSettings::class);
        $mayViewDefaults = Gate::allows('viewAny', DefaultClientNotification::class);

        if (! $mayViewSettings && ! $mayViewDefaults) {
            throw new AuthorizationException;
        }

        return view('admin.settings.edit', [
            'mayViewSettings' => $mayViewSettings,
            'mayViewDefaults' => $mayViewDefaults,
            'settings' => ApplicationSettings::current(),
            'timezones' => TimezoneIdentifier::all(),
            'defaults' => $mayViewDefaults
                ? DefaultClientNotification::query()->orderBy('id')->get()
                : collect(),
            'templatePreviews' => $mayViewDefaults ? $templatePreviews->all() : [],
        ]);
    }

    public function update(
        UpdateApplicationSettingsRequest $request,
        UpdateApplicationSettingsAction $updateSettings,
    ): RedirectResponse {
        $this->authorize('update', ApplicationSettings::class);

        $updateSettings($request->toData());

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', __('settings.flash.updated'));
    }
}
