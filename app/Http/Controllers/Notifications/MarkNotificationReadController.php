<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MarkNotificationReadController extends Controller
{
    public function __invoke(Request $request, string $notification): RedirectResponse
    {
        // Looking the notification up through the user is what guarantees nobody can
        // read, or read away, somebody else's notification.
        $found = $request->user()->notifications()->findOrFail($notification);

        $found->markAsRead();

        $path = $found->data['path'] ?? null;

        // Notifications store a path rather than a full address, because the process
        // that writes them is usually the scheduler, which has no idea which host the
        // reader will use. Only an in-application path is ever followed.
        return is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//')
            ? redirect()->to($path)
            : redirect()->route('notifications.index');
    }
}
