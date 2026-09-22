<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAttributeFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only way to reach a file somebody uploaded against a client attribute.
 *
 * Uploads are written to the private disk, so there is no URL that serves them and no
 * link that works without going through here. Who may read one is exactly who may read
 * the client it belongs to — asked of the client policy rather than answered again —
 * which is what keeps "who can see this file" a single decision.
 *
 * The owner is read off the file row rather than off the answer that points at it. An
 * answer is JSON on a record anybody who can edit the client can edit, so trusting it
 * would let one client's record name another client's file.
 *
 * It is always sent as a download. A file is never rendered in the browser, so an
 * uploaded document cannot run as a page on this application's own origin.
 */
final class ClientAttributeFileController extends Controller
{
    public function __invoke(ClientAttributeFile $file): StreamedResponse
    {
        $client = $file->client;

        abort_unless($client instanceof Client, 404);

        $this->authorize('view', $client);

        $disk = Storage::disk($file->disk);

        // The row can outlive its bytes — a rollback after they were deleted, a disk
        // restored from a backup — and a missing file is a missing page, not an error.
        abort_unless($disk->exists($file->path), 404);

        return $disk->download($file->path, $file->original_name);
    }
}
