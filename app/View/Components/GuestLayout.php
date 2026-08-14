<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\ColorScheme;
use App\Models\ApplicationSettings;
use App\Support\ActiveTheme;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The shell for the few pages a guest may reach.
 */
final class GuestLayout extends Component
{
    public function __construct(
        public ?string $title = null,
    ) {}

    public function render(): View
    {
        return view('layouts.guest', [
            'applicationName' => ApplicationSettings::current()->application_name,
            'colorScheme' => ColorScheme::System,
            'theme' => ActiveTheme::applicationDefault(),
        ]);
    }
}
