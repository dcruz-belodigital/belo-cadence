<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/**
 * Writes an upload to the disk and records who it belongs to.
 *
 * The bytes go to the private disk under a generated name: nothing a person uploaded is
 * ever reachable by URL, and the name they chose is kept only as the name the download
 * gives it back under, never as a path.
 *
 * It records no audit entry. A file only ever arrives because somebody saved a client,
 * and that client's own entry carries the answer that now points at it — the same
 * reasoning that keeps `SyncClientAttributeValuesAction` out of the audit log.
 *
 * Both callers hold a transaction, so the bytes can outlive a rollback that takes the
 * row with it. `cadence:prune-attribute-files` clears up after exactly that.
 */
final class StoreClientAttributeFileAction
{
    public function __invoke(Client $client, ClientAttribute $attribute, UploadedFile $upload): ClientAttributeFile
    {
        $path = $upload->store(ClientAttributeFile::DIRECTORY, ClientAttributeFile::DISK);

        return ClientAttributeFile::query()->create([
            'client_id' => $client->getKey(),
            'client_attribute_id' => $attribute->getKey(),
            'uploaded_by_user_id' => Auth::id(),
            'disk' => ClientAttributeFile::DISK,
            'path' => (string) $path,
            'original_name' => $upload->getClientOriginalName(),
            // Detected rather than taken from the browser, which says whatever it likes.
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'size' => (int) $upload->getSize(),
        ]);
    }
}
