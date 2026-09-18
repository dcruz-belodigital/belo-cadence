<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Data\Clients\ClientAttributeValuesData;
use App\Enums\ClientAttributeType;
use App\Models\ClientAttribute;
use App\Rules\ClientAttributeValueRule;
use App\ValueObjects\ClientAttributeField;
use Illuminate\Database\Eloquent\Collection;

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
 */
trait ValidatesClientAttributes
{
    /**
     * @var Collection<int, ClientAttribute>|null
     */
    private ?Collection $activeClientAttributes = null;

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
                $attribute->type,
                $attribute->fields,
                $submitted[$attribute->getKey()] ?? null,
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
     */
    private function normaliseAnswer(ClientAttributeType $type, array $fields, mixed $value): mixed
    {
        if (! $type->usesFields()) {
            return $type->normalise($value);
        }

        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = [];

            foreach ($fields as $field) {
                $cells[$field->key] = $this->normaliseAnswer($field->type, $field->fields, $row[$field->key] ?? null);
            }

            // A row the browser added and nobody typed in is not a row.
            if ($this->holdsSomething($cells)) {
                $rows[] = $cells;
            }
        }

        return $rows;
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
