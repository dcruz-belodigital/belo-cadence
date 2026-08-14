<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\RestoreClientAction;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;

final class RestoreClientController extends Controller
{
    public function __invoke(Client $client, RestoreClientAction $restoreClient): RedirectResponse
    {
        $this->authorize('restore', $client);

        $restoreClient($client);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', __('clients.flash.restored', ['name' => $client->name]));
    }
}
