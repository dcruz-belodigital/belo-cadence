<?php

declare(strict_types=1);

use App\Models\ClientAttributeFile;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

/**
 * A file is deleted along with its row the moment nothing points at it, so this command
 * only ever finds bytes written inside a transaction that then rolled back — the one case
 * where a row claiming them was never committed.
 */
beforeEach(function (): void {
    Storage::fake(ClientAttributeFile::DISK);
});

it('deletes bytes no record claims and keeps the ones that are claimed', function (): void {
    $claimed = ClientAttributeFile::factory()->stored()->create();
    $orphan = ClientAttributeFile::DIRECTORY.'/orphan.pdf';

    Storage::disk(ClientAttributeFile::DISK)->put($orphan, '%PDF-1.4');
    touch(Storage::disk(ClientAttributeFile::DISK)->path($orphan), CarbonImmutable::now()->subDays(3)->getTimestamp());
    touch(Storage::disk(ClientAttributeFile::DISK)->path($claimed->path), CarbonImmutable::now()->subDays(3)->getTimestamp());

    artisan('cadence:prune-attribute-files')->assertSuccessful();

    Storage::disk(ClientAttributeFile::DISK)->assertMissing($orphan);
    Storage::disk(ClientAttributeFile::DISK)->assertExists($claimed->path);
});

it('leaves an unclaimed file that is too young to be abandoned', function (): void {
    $fresh = ClientAttributeFile::DIRECTORY.'/in-flight.pdf';

    Storage::disk(ClientAttributeFile::DISK)->put($fresh, '%PDF-1.4');

    artisan('cadence:prune-attribute-files')->assertSuccessful();

    Storage::disk(ClientAttributeFile::DISK)->assertExists($fresh);
});

it('is scheduled to run on its own', function (): void {
    $commands = collect(app(Schedule::class)->events())->map(fn ($event): string => (string) $event->command);

    expect($commands->contains(fn (string $command): bool => str_contains($command, 'cadence:prune-attribute-files')))->toBeTrue();
});
