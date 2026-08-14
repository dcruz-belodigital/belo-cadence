<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\ColorScheme;
use App\Models\ApplicationSettings;
use App\Models\User;
use App\Support\ActiveTheme;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The authenticated application shell: sidebar, top bar and main content.
 *
 * The page's name and its way back live in the top bar, so a page starts with its own
 * content rather than repeating a heading below the chrome.
 *
 * The shell also owns how wide the page is. A page picks one of two widths and the
 * layout applies it to everything at once, which is what keeps the description and
 * the content it describes on the same left edge — a page that constrained itself
 * would leave the description stranded in a wider container.
 */
final class AppLayout extends Component
{
    /**
     * @param  'wide'|'narrow'  $width  Wide for tables and index pages, narrow for forms and reading pages.
     */
    public function __construct(
        public string $heading,
        public ?string $title = null,
        public ?string $back = null,
        public ?string $backLabel = null,
        public string $width = 'wide',
    ) {}

    /**
     * Written out in full rather than assembled, so Tailwind can see both class names.
     */
    public function containerClass(): string
    {
        return $this->width === 'narrow' ? 'max-w-narrow' : 'max-w-wide';
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('layouts.app', [
            'documentTitle' => $this->title ?? $this->heading,
            'applicationName' => ApplicationSettings::current()->application_name,
            'colorScheme' => $user instanceof User ? $user->color_scheme : ColorScheme::System,
            'theme' => ActiveTheme::for($user instanceof User ? $user : null),
        ]);
    }
}
