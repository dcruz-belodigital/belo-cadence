<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The unread-notification indicator in the top bar.
 *
 * Presentation only: it reads the signed-in user's notifications and nothing else.
 */
final class NotificationBell extends Component
{
    private const RECENT_LIMIT = 6;

    public function render(): View
    {
        $user = Auth::user();

        return view('components.notification-bell', [
            'unreadCount' => $user instanceof User ? $user->unreadNotifications()->count() : 0,
            'recent' => $user instanceof User
                ? $user->notifications()->latest()->limit(self::RECENT_LIMIT)->get()
                : new Collection,
        ]);
    }
}
