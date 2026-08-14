<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateDefaultClientNotificationsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateDefaultClientNotificationsRequest;
use App\Models\DefaultClientNotification;
use Illuminate\Http\RedirectResponse;

/**
 * The default notifications are a section of the settings page, not a page of their own,
 * so this holds only the form's own endpoint. It keeps its separate permission and audit
 * entry: sharing a page does not mean sharing who may change what.
 */
final class DefaultClientNotificationController extends Controller
{
    public function update(
        UpdateDefaultClientNotificationsRequest $request,
        UpdateDefaultClientNotificationsAction $updateDefaults,
    ): RedirectResponse {
        $this->authorize('update', DefaultClientNotification::class);

        $updateDefaults($request->toData());

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', __('settings.defaults.flash.updated'));
    }
}
