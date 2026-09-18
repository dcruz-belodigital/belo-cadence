<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Clients\UpdateClientData;
use App\Enums\AuditAction;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * Updates a client's own details.
 *
 * Deliveries already produced for this client keep the name and address they were
 * sent with: nothing here reaches into history.
 */
final class UpdateClientAction
{
    public function __construct(
        private readonly SyncClientAttributeValuesAction $syncAttributeValues,
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(Client $client, UpdateClientData $data): Client
    {
        return DB::transaction(function () use ($client, $data): Client {
            $before = $client->auditShape();

            $client->update([
                'name' => $data->name,
                'email' => $data->email,
                'status' => $data->status,
                'notes' => $data->notes,
            ]);

            ($this->syncAttributeValues)($client, $data->attributeValues);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::ClientUpdated,
                auditable: $client,
                oldValues: $before,
                newValues: $client->auditShape(),
            ));

            return $client;
        });
    }
}
