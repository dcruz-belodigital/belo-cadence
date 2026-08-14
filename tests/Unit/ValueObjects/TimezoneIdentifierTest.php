<?php

declare(strict_types=1);

use App\ValueObjects\TimezoneIdentifier;

it('accepts a known identifier', function (): void {
    expect((new TimezoneIdentifier('Europe/Lisbon'))->value)
        ->toBe('Europe/Lisbon');
});

it('trims surrounding whitespace', function (): void {
    expect((new TimezoneIdentifier('  UTC '))->value)
        ->toBe('UTC');
});

it('converts to a date timezone', function (): void {
    expect((new TimezoneIdentifier('Europe/Lisbon'))->toDateTimeZone()->getName())
        ->toBe('Europe/Lisbon');
});

it('provides utc as a named constructor', function (): void {
    expect(TimezoneIdentifier::utc()->value)->toBe('UTC');
});

it('compares two identifiers', function (): void {
    expect((new TimezoneIdentifier('UTC'))->equals(TimezoneIdentifier::utc()))->toBeTrue()
        ->and((new TimezoneIdentifier('UTC'))->equals(new TimezoneIdentifier('Europe/Lisbon')))->toBeFalse();
});

it('rejects an unknown identifier', function (string $value): void {
    expect(fn (): TimezoneIdentifier => new TimezoneIdentifier($value))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => [''],
    'made up' => ['Mars/Olympus_Mons'],
    'offset only' => ['+02:00'],
    'wrong casing' => ['europe/lisbon'],
]);
