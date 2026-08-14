<?php

declare(strict_types=1);

use App\ValueObjects\EmailAddress;

it('accepts a valid address', function (): void {
    expect((new EmailAddress('person@example.com'))->value)
        ->toBe('person@example.com');
});

it('normalises casing and surrounding whitespace', function (): void {
    expect((new EmailAddress('  Person@Example.COM  '))->value)
        ->toBe('person@example.com');
});

it('exposes the domain', function (): void {
    expect((new EmailAddress('person@mail.example.com'))->domain())
        ->toBe('mail.example.com');
});

it('compares two addresses by their normalised value', function (): void {
    expect((new EmailAddress('Person@example.com'))->equals(new EmailAddress('person@example.com')))
        ->toBeTrue();
});

it('can be printed and serialised', function (): void {
    $email = new EmailAddress('person@example.com');

    expect((string) $email)->toBe('person@example.com')
        ->and(json_encode($email))->toBe('"person@example.com"');
});

it('rejects an invalid address', function (string $value): void {
    expect(fn (): EmailAddress => new EmailAddress($value))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => [''],
    'no at sign' => ['person.example.com'],
    'no domain' => ['person@'],
    'no local part' => ['@example.com'],
    'spaces inside' => ['per son@example.com'],
    'no top level domain' => ['person@example'],
]);
