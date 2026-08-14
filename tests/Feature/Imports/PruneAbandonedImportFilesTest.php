<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

it('deletes an abandoned file and leaves a fresh one alone', function (): void {
    Storage::fake('local');

    Storage::disk('local')->put('imports/abandoned.csv', 'name,email');
    Storage::disk('local')->put('imports/waiting.csv', 'name,email');

    // Only the older file is past the cutoff.
    touch(Storage::disk('local')->path('imports/abandoned.csv'), CarbonImmutable::now()->subDays(3)->getTimestamp());

    artisan('cadence:prune-import-files')->assertSuccessful();

    expect(Storage::disk('local')->exists('imports/abandoned.csv'))->toBeFalse()
        ->and(Storage::disk('local')->exists('imports/waiting.csv'))->toBeTrue();
});

it('accepts a shorter window', function (): void {
    Storage::fake('local');

    Storage::disk('local')->put('imports/recent.csv', 'name,email');
    touch(Storage::disk('local')->path('imports/recent.csv'), CarbonImmutable::now()->subHours(2)->getTimestamp());

    artisan('cadence:prune-import-files --hours=1')->assertSuccessful();

    expect(Storage::disk('local')->exists('imports/recent.csv'))->toBeFalse();
});

it('does nothing when there is nothing to clear', function (): void {
    Storage::fake('local');

    artisan('cadence:prune-import-files')->assertSuccessful();
});

it('is scheduled to run on its own', function (): void {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event): string => (string) $event->command);

    expect($commands->contains(fn (string $command): bool => str_contains($command, 'cadence:prune-import-files')))->toBeTrue()
        ->and($commands->contains(fn (string $command): bool => str_contains($command, 'cadence:process-notifications')))->toBeTrue();
});
