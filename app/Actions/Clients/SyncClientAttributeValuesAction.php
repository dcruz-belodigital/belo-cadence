<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Data\Clients\ClientAttributeValuesData;
use App\Models\Client;

/**
 * Writes a client's answers to its attributes.
 *
 * It records no audit entry of its own. Answers never change on their own — they change
 * because somebody saved a client — so the client's own entry carries them in its
 * before-and-after diff, and a second entry would say the same thing twice. The same
 * reasoning keeps a send out of the audit log: the surrounding record is the history.
 *
 * It is only ever called from inside `CreateClientAction` or `UpdateClientAction`, which
 * already hold the transaction, so it opens none of its own.
 *
 * Absent means untouched, and blank means cleared — see `ClientAttributeValuesData`.
 * Clearing deletes the row rather than storing a null, which is what keeps "how many
 * clients still use this attribute" an honest answer.
 */
final class SyncClientAttributeValuesAction
{
    public function __invoke(Client $client, ClientAttributeValuesData $data): void
    {
        foreach ($data->values as $attributeId => $value) {
            if ($this->isBlank($value)) {
                $client->attributeValues()->where('client_attribute_id', $attributeId)->delete();

                continue;
            }

            $client->attributeValues()->updateOrCreate(
                ['client_attribute_id' => $attributeId],
                ['value' => $value],
            );
        }
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null
            || $value === []
            || (is_string($value) && trim($value) === '');
    }
}
