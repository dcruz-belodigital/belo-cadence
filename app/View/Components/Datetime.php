<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Support\ViewerTimezone;
use DateTimeInterface;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Renders a stored UTC timestamp in the reader's timezone.
 *
 * Views never convert timezones or choose date formats themselves; they use this.
 */
final class Datetime extends Component
{
    public function __construct(
        private readonly ViewerTimezone $viewerTimezone,
        public ?DateTimeInterface $value = null,
        public string $format = 'datetime',
        public string $placeholder = '—',
        public bool $withTimezone = false,
    ) {}

    public function render(): View
    {
        return view('components.datetime', [
            'formatted' => $this->value === null
                ? null
                : $this->viewerTimezone->format($this->value, $this->pattern()),
            'timezone' => $this->viewerTimezone->current()->value,
            'iso' => $this->value?->format(DATE_ATOM),
        ]);
    }

    private function pattern(): string
    {
        return (string) __('common.formats.'.$this->format);
    }
}
