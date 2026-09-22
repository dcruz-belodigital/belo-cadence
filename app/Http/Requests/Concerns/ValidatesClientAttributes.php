<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Data\Clients\ClientAttributeValuesData;
use App\Enums\ClientAttributeType;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use App\Rules\ClientAttributeValueRule;
use App\ValueObjects\ClientAttributeField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

/**
 * The custom attributes half of the client form, shared by creating and editing.
 *
 * The fields on this form are not known until the definitions are read, so the rules are
 * built per request. There is one rule key per attribute however deep its answer nests,
 * because every key needs somewhere on the page to appear — and a repeater's rows are
 * drawn by the browser, so they have nowhere of their own.
 *
 * The input is named `client_attributes` rather than `attributes`: `Illuminate\Http\Request`
 * already has a public `$attributes` property and `FormRequest` already has an
 * `attributes()` method, and a rule key sitting between the two would be a trap.
 *
 * A file attribute is the one answer that is not typed. Its cell submits two things — the
 * upload, and the id of a file already held — and normalisation reduces them to one
 * value: the `UploadedFile`, the id, or null. Everything downstream then treats a file
 * like any other answer, at any depth.
 */
trait ValidatesClientAttributes
{
    /**
     * @var Collection<int, ClientAttribute>|null
     */
    private ?Collection $activeClientAttributes = null;

    /**
     * Which file this client holds, keyed by id: `id => client_attribute_id`.
     *
     * @var array<int, int>|null
     */
    private ?array $heldFiles = null;

    /**
     * Every rule the attributes half of this form can produce.
     *
     * Public because `FormErrorTest` derives the keys it checks from it: the form's rule
     * keys depend on database rows, so writing them out by hand in the test would let a
     * new one slip through with nowhere to appear.
     *
     * @return array<string, mixed>
     */
    public function clientAttributeRules(): array
    {
        $rules = ['client_attributes' => ['array']];

        foreach ($this->activeClientAttributes() as $attribute) {
            $rules['client_attributes.'.$attribute->getKey()] = ClientAttributeValueRule::for($attribute);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function clientAttributeNames(): array
    {
        $names = [];

        foreach ($this->activeClientAttributes() as $attribute) {
            $names['client_attributes.'.$attribute->getKey()] = $attribute->name;
        }

        return $names;
    }

    /**
     * @return Collection<int, ClientAttribute>
     */
    public function activeClientAttributes(): Collection
    {
        return $this->activeClientAttributes ??= ClientAttribute::query()->active()->get();
    }

    /**
     * The payload the rules actually run against.
     *
     * `Request::all()` lays the uploaded files back over the input at the position they
     * were submitted in. The answers have already been normalised by the time a rule sees
     * them — empty rows dropped, the remaining ones renumbered and every upload read in
     * where it belongs — so letting that happen a second time would drop a file back at a
     * row index that no longer exists. Nothing else on these forms uploads anything, so
     * only this one key is held back from the overlay.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'client_attributes' => $this->input('client_attributes'),
        ];
    }

    /**
     * Normalises the submitted answers before anything is validated.
     *
     * Every active attribute ends up present, missing ones as null, so this form always
     * replaces rather than patches — a repeater emptied in the browser submits nothing at
     * all, and would otherwise look like "leave it alone". Anything submitted for an
     * attribute that is not active is dropped rather than reported, which is what stops a
     * tampered payload writing to a retired one.
     */
    protected function prepareClientAttributeInput(): void
    {
        $submitted = is_array($this->input('client_attributes')) ? $this->input('client_attributes') : [];

        $answers = [];

        foreach ($this->activeClientAttributes() as $attribute) {
            $answers[$attribute->getKey()] = $this->normaliseAnswer(
                $attribute,
                $attribute->type,
                $attribute->fields,
                $submitted[$attribute->getKey()] ?? null,
                'client_attributes.'.$attribute->getKey(),
            );
        }

        $this->merge(['client_attributes' => $answers]);
    }

    public function clientAttributeValues(): ClientAttributeValuesData
    {
        $validated = $this->validated('client_attributes');

        /** @var array<int, mixed> $answers */
        $answers = is_array($validated) ? $validated : [];

        return new ClientAttributeValuesData($answers);
    }

    /**
     * @param  list<ClientAttributeField>  $fields
     * @param  string  $path  Where this value sits in the submitted payload, so an upload
     *                        can be read from the request at the position it arrived in.
     */
    private function normaliseAnswer(
        ClientAttribute $attribute,
        ClientAttributeType $type,
        array $fields,
        mixed $value,
        string $path,
    ): mixed {
        if ($type->usesFile()) {
            return $this->fileAnswer($attribute, $value, $path);
        }

        if (! $type->usesFields()) {
            return $type->normalise($value);
        }

        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = [];

            foreach ($fields as $field) {
                $cells[$field->key] = $this->normaliseAnswer(
                    $attribute,
                    $field->type,
                    $field->fields,
                    $row[$field->key] ?? null,
                    $path.'.'.$index.'.'.$field->key,
                );
            }

            // A row the browser added and nobody typed in is not a row.
            if ($this->holdsSomething($cells)) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /**
     * What one file cell comes to: a new upload, a file already held, or nothing.
     *
     * A fresh upload always wins, because choosing a file is how somebody replaces one.
     */
    private function fileAnswer(ClientAttribute $attribute, mixed $value, string $path): UploadedFile|int|null
    {
        $upload = $this->file($path.'.file');

        if ($upload instanceof UploadedFile) {
            return $upload;
        }

        $keep = is_array($value) ? ($value['keep'] ?? null) : null;

        if (! is_numeric($keep)) {
            return null;
        }

        /*
        | Resolved against what this client actually holds, so an id typed into the form
        | by hand reaches nothing: a file belongs to one client and one attribute, and
        | anything else simply reads as an unanswered field.
        */
        return ($this->heldFiles()[(int) $keep] ?? null) === $attribute->getKey()
            ? (int) $keep
            : null;
    }

    /**
     * @return array<int, int>
     */
    private function heldFiles(): array
    {
        if ($this->heldFiles !== null) {
            return $this->heldFiles;
        }

        $client = $this->route('client');

        // A client being created holds nothing yet, so there is nothing it could keep.
        if (! $client instanceof Client) {
            return $this->heldFiles = [];
        }

        /** @var array<int, int> $held */
        $held = ClientAttributeFile::query()
            ->where('client_id', $client->getKey())
            ->pluck('client_attribute_id', 'id')
            ->all();

        return $this->heldFiles = $held;
    }

    /**
     * @param  array<string, mixed>  $cells
     */
    private function holdsSomething(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== null && $cell !== [] && $cell !== '' && $cell !== false) {
                return true;
            }
        }

        return false;
    }
}
