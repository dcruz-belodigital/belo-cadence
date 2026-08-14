<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Enums\AuditAction;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * Brings an archived client back.
 *
 * Its schedules stay switched off: somebody has to decide deliberately which
 * communication should start again.
 */
final class RestoreClientAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(Client $client): Client
    {
        return DB::transaction(function () use ($client): Client {
            $client->restore();

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientRestored,
                auditable: $client,
                newValues: [
                    'name' => $client->name,
                    'email' => $client->email->value,
                    'status' => $client->status->value,
                ],
            ));

            return $client;
        });
    }
}
