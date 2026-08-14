<?php

declare(strict_types=1);

namespace App\Support\Csv;

use App\Data\Imports\PendingImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

/**
 * Holds an uploaded file between the upload and the confirmation.
 *
 * The file is kept outside the public directory and remembered per person, in their
 * session, so one import cannot see another's file. It is deleted as soon as the import
 * finishes, and when the same person starts a new one.
 */
final class PendingImportStore
{
    private const DIRECTORY = 'imports';

    private const SESSION_PREFIX = 'pending_import.';

    public function put(string $resource, UploadedFile $file): PendingImport
    {
        $this->forget($resource);

        $path = $file->store(self::DIRECTORY, 'local');

        $pending = new PendingImport(
            storedPath: (string) $path,
            originalName: $file->getClientOriginalName(),
        );

        Session::put(self::SESSION_PREFIX.$resource, [
            'path' => $pending->storedPath,
            'name' => $pending->originalName,
        ]);

        return $pending;
    }

    public function get(string $resource): ?PendingImport
    {
        /** @var array{path?: string, name?: string}|null $stored */
        $stored = Session::get(self::SESSION_PREFIX.$resource);

        if (! is_array($stored) || ! isset($stored['path'])) {
            return null;
        }

        if (! Storage::disk('local')->exists($stored['path'])) {
            $this->forget($resource);

            return null;
        }

        return new PendingImport($stored['path'], (string) ($stored['name'] ?? $stored['path']));
    }

    public function absolutePath(PendingImport $pending): string
    {
        return Storage::disk('local')->path($pending->storedPath);
    }

    public function forget(string $resource): void
    {
        $pending = Session::get(self::SESSION_PREFIX.$resource);

        if (is_array($pending) && isset($pending['path'])) {
            Storage::disk('local')->delete($pending['path']);
        }

        Session::forget(self::SESSION_PREFIX.$resource);
    }
}
