<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ApplicationSettings;
use App\Models\User;
use App\ValueObjects\TimezoneIdentifier;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;

/**
 * The timezone the current page is read and written in.
 *
 * Timestamps are stored in UTC. Every conversion for display or from form input goes
 * through here so no view has to think about timezones.
 */
final class ViewerTimezone
{
    private ?TimezoneIdentifier $resolved = null;

    /**
     * Whose timezone `$resolved` belongs to: a user key, or null for a guest.
     */
    private int|string|null $resolvedFor = null;

    /**
     * The signed-in user's preference, falling back to the application default.
     *
     * The answer is remembered so a page full of timestamps costs one lookup, but it is
     * remembered against the reader it was resolved for. Anything that switches user
     * inside one process — a test, a console command, a long-running worker — therefore
     * gets its own answer rather than the previous reader's.
     */
    public function current(): TimezoneIdentifier
    {
        $user = Auth::user();
        $readerKey = $user instanceof User ? $user->getKey() : null;

        if ($this->resolved instanceof TimezoneIdentifier && $this->resolvedFor === $readerKey) {
            return $this->resolved;
        }

        $this->resolvedFor = $readerKey;

        return $this->resolved = $user instanceof User
            ? $user->timezone
            : ApplicationSettings::current()->default_timezone;
    }

    /**
     * Read a datetime the user typed in their own timezone and store it as UTC.
     */
    public function toUtc(string $localDateTime): CarbonImmutable
    {
        return CarbonImmutable::parse($localDateTime, $this->current()->toDateTimeZone())
            ->setTimezone('UTC');
    }

    public function toViewer(DateTimeInterface $value): CarbonImmutable
    {
        return CarbonImmutable::instance($value)->setTimezone($this->current()->toDateTimeZone());
    }

    public function format(DateTimeInterface $value, string $format): string
    {
        return $this->toViewer($value)->format($format);
    }

    /**
     * The value shown by a `datetime-local` input for an existing timestamp.
     */
    public function toInputValue(DateTimeInterface $value): string
    {
        return $this->format($value, 'Y-m-d\TH:i');
    }
}
