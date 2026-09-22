<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Data\Clients\ClientAttributeValuesData;
use App\Enums\ClientAttributeType;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use App\ValueObjects\ClientAttributeField;
use App\ValueObjects\StoredFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

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
 *
 * A file answer takes two more steps, at whatever depth it sits. An upload is written to
 * the disk and the answer becomes a reference to it; and once the answer is written,
 * every file this client holds for that attribute that the new answer no longer points
 * at is deleted. That single sweep is what covers replacing a file, clearing one, and
 * removing the repeating row that held it — rather than three rules that could disagree.
 */
final class SyncClientAttributeValuesAction
{
    public function __construct(
        private readonly StoreClientAttributeFileAction $storeFile,
        private readonly DeleteClientAttributeFilesAction $deleteFiles,
    ) {}

    public function __invoke(Client $client, ClientAttributeValuesData $data): void
    {
        $attributes = $this->definitionsFor($data);

        foreach ($data->values as $attributeId => $value) {
            $attribute = $attributes->get($attributeId);

            $value = $attribute instanceof ClientAttribute
                ? $this->storeUploads($client, $attribute, $attribute->type, $attribute->fields, $value)
                : $value;

            if ($this->isBlank($value)) {
                $client->attributeValues()->where('client_attribute_id', $attributeId)->delete();
            } else {
                $client->attributeValues()->updateOrCreate(
                    ['client_attribute_id' => $attributeId],
                    ['value' => $value],
                );
            }

            if ($attribute instanceof ClientAttribute && $attribute->holdsFiles()) {
                ($this->deleteFiles)($this->unreferencedFiles($client, $attribute, $value));
            }
        }
    }

    /**
     * The definitions behind the answers being written.
     *
     * Only needed to find the files inside them, so nothing is loaded for a payload that
     * has none — which is every CSV import, and every client form with no file attribute.
     *
     * @return Collection<int, ClientAttribute>
     */
    private function definitionsFor(ClientAttributeValuesData $data): Collection
    {
        /** @var Collection<int, ClientAttribute> $empty */
        $empty = new Collection;

        if ($data->isEmpty()) {
            return $empty;
        }

        /** @var Collection<int, ClientAttribute> $attributes */
        $attributes = ClientAttribute::query()->findMany(array_keys($data->values))->keyBy('id');

        return $attributes;
    }

    /**
     * Replaces every upload in one answer with a reference to the file it was stored as.
     *
     * The answer is walked against its definition rather than guessed at, so a file three
     * repeating rows deep is found the same way one at the top is.
     *
     * @param  list<ClientAttributeField>  $fields
     */
    private function storeUploads(
        Client $client,
        ClientAttribute $attribute,
        ClientAttributeType $type,
        array $fields,
        mixed $value,
    ): mixed {
        if ($type->usesFile()) {
            return $this->fileValue($client, $attribute, $value);
        }

        if (! $type->usesFields() || ! is_array($value)) {
            return $value;
        }

        $rows = [];

        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! array_key_exists($field->key, $row)) {
                    continue;
                }

                $row[$field->key] = $this->storeUploads(
                    $client,
                    $attribute,
                    $field->type,
                    $field->fields,
                    $row[$field->key],
                );
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * What one file cell becomes: a reference, or nothing at all.
     *
     * The form has already reduced the cell to an upload or to the id of a file this
     * client holds, so an id that resolves to nothing here means the file has since been
     * deleted and the answer is simply empty.
     *
     * @return array<string, mixed>|null
     */
    private function fileValue(Client $client, ClientAttribute $attribute, mixed $value): ?array
    {
        if ($value instanceof UploadedFile) {
            return ($this->storeFile)($client, $attribute, $value)->toValue()->toArray();
        }

        if (! is_int($value)) {
            return null;
        }

        $file = ClientAttributeFile::query()
            ->where('client_id', $client->getKey())
            ->where('client_attribute_id', $attribute->getKey())
            ->find($value);

        return $file?->toValue()->toArray();
    }

    /**
     * The files this client holds for one attribute that its new answer does not use.
     *
     * @return Collection<int, ClientAttributeFile>
     */
    private function unreferencedFiles(Client $client, ClientAttribute $attribute, mixed $value): Collection
    {
        $referenced = array_map(
            static fn (StoredFile $file): int => $file->id,
            StoredFile::allIn($value),
        );

        /** @var Collection<int, ClientAttributeFile> $files */
        $files = ClientAttributeFile::query()
            ->where('client_id', $client->getKey())
            ->where('client_attribute_id', $attribute->getKey())
            ->whereNotIn('id', $referenced)
            ->get();

        return $files;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null
            || $value === []
            || (is_string($value) && trim($value) === '');
    }
}
