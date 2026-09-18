<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Data\ClientAttributes\ClientAttributeData;
use App\Enums\ClientAttributeType;
use App\Models\ClientAttribute;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The half of the attribute form that creating and editing share.
 *
 * A repeater's fields are a tree, and the rules for it are generated one level at a time
 * up to `ClientAttribute::MAX_DEPTH` rather than written out, so raising the cap is a
 * constant rather than a rewrite. Every one of those keys is reported by a single
 * `x-form.error` above the rows, because the rows themselves are drawn by the browser.
 */
trait DefinesClientAttributes
{
    /**
     * The most fields one row may be made of.
     */
    public const MAX_FIELDS = 20;

    /**
     * The attribute being edited, or null while one is being created.
     */
    abstract protected function definition(): ?ClientAttribute;

    /**
     * @return array<string, mixed>
     */
    protected function definitionRules(): array
    {
        $rules = [
            'hint' => ['nullable', 'string', 'max:500'],
            /*
            | `required` rather than a closure: TrimStrings and ConvertEmptyStringsToNull
            | turn a blank textarea into null before validation, and `nullable` would then
            | skip any rule that is not implicit.
            */
            'options' => [
                Rule::requiredIf(fn (): bool => $this->submittedType() === ClientAttributeType::Select),
                'nullable', 'string', 'max:5000',
            ],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'position' => ['required', 'integer', 'min:0', 'max:9999'],
            'fields' => ['array', 'max:'.self::MAX_FIELDS, $this->isMadeOfSomething(), $this->staysWithinDepth()],
        ];

        foreach ($this->fieldPrefixes() as $prefix) {
            $rules[$prefix] ??= ['array', 'max:'.self::MAX_FIELDS];
            $rules[$prefix.'.*.key'] = ['nullable', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'];
            $rules[$prefix.'.*.name'] = ['required', 'string', 'max:255'];
            $rules[$prefix.'.*.type'] = ['required', Rule::enum(ClientAttributeType::class)];
            $rules[$prefix.'.*.is_required'] = ['boolean'];
            $rules[$prefix.'.*.options'] = ['nullable', 'string', 'max:5000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function definitionAttributes(): array
    {
        $names = [
            'hint' => __('client_attributes.fields.hint'),
            'options' => __('client_attributes.fields.options'),
            'is_required' => __('client_attributes.fields.is_required'),
            'is_active' => __('client_attributes.fields.is_active'),
            'position' => __('client_attributes.fields.position'),
            'fields' => __('client_attributes.fields.fields'),
        ];

        foreach ($this->fieldPrefixes() as $prefix) {
            $names[$prefix.'.*.name'] = __('client_attributes.fields.field_name');
            $names[$prefix.'.*.type'] = __('client_attributes.fields.field_type');
            $names[$prefix.'.*.is_required'] = __('client_attributes.fields.field_required');
        }

        return $names;
    }

    /**
     * Rows the browser added and nobody filled in arrive empty, so they are dropped
     * before validation rather than reported as mistakes.
     */
    protected function prepareDefinitionInput(): void
    {
        $this->merge([
            'fields' => $this->pruneFields(is_array($this->input('fields')) ? $this->input('fields') : []),
        ]);
    }

    protected function toDefinitionData(ClientAttributeType $type): ClientAttributeData
    {
        $existing = $this->definition();

        $name = $this->string('name')->trim()->toString();

        return new ClientAttributeData(
            key: $existing instanceof ClientAttribute ? $existing->key : self::keyFor($name),
            name: $name,
            type: $type,
            hint: $this->filled('hint') ? $this->string('hint')->trim()->toString() : null,
            options: $this->mergedChoices($existing?->options, $this->string('options')->toString()),
            fields: $this->parseFields(
                is_array($this->validated('fields')) ? $this->validated('fields') : [],
                $existing?->fields ?? [],
            ),
            isRequired: $this->boolean('is_required'),
            isActive: $this->boolean('is_active'),
            position: $this->integer('position'),
        );
    }

    /**
     * The machine name derived from what somebody typed.
     */
    public static function keyFor(string $name): string
    {
        return Str::limit(Str::slug($name, '_'), 64, '');
    }

    /**
     * The rule key of every level of the tree the form can submit.
     *
     * @return list<string>
     */
    private function fieldPrefixes(): array
    {
        $prefixes = ['fields'];

        for ($level = 1; $level < ClientAttribute::MAX_DEPTH; $level++) {
            $prefixes[] = end($prefixes).'.*.fields';
        }

        return $prefixes;
    }

    /**
     * Repeating rows with no fields are rows of nothing.
     */
    private function isMadeOfSomething(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->submittedType() !== ClientAttributeType::Repeater) {
                return;
            }

            if (! is_array($value) || $value === []) {
                $fail(__('client_attributes.errors.repeater_needs_field'));
            }
        };
    }

    /**
     * The editor only draws `MAX_DEPTH` levels, so anything deeper was not typed here.
     */
    private function staysWithinDepth(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_array($value) && $this->depthOf($value) > ClientAttribute::MAX_DEPTH) {
                $fail(__('client_attributes.errors.too_deep', ['levels' => ClientAttribute::MAX_DEPTH]));
            }
        };
    }

    /**
     * @param  array<int, mixed>  $fields
     */
    private function depthOf(array $fields, int $level = 1): int
    {
        $deepest = $level;

        foreach ($fields as $field) {
            if (is_array($field) && is_array($field['fields'] ?? null) && $field['fields'] !== []) {
                $deepest = max($deepest, $this->depthOf($field['fields'], $level + 1));
            }
        }

        return $deepest;
    }

    /**
     * @param  array<int, mixed>  $fields
     * @return list<array<string, mixed>>
     */
    private function pruneFields(array $fields): array
    {
        $kept = [];

        foreach ($fields as $field) {
            if (! is_array($field) || trim((string) ($field['name'] ?? '')) === '') {
                continue;
            }

            if (is_array($field['fields'] ?? null)) {
                $field['fields'] = $this->pruneFields($field['fields']);
            }

            $kept[] = $field;
        }

        return $kept;
    }

    /**
     * Turns the submitted rows into fields, keeping the choices a sub-field already had
     * so removing one from its list retires it rather than losing it.
     *
     * @param  array<int, mixed>  $submitted
     * @param  list<ClientAttributeField>  $existing
     * @return list<ClientAttributeField>
     */
    private function parseFields(array $submitted, array $existing, int $level = 1): array
    {
        if ($level > ClientAttribute::MAX_DEPTH) {
            return [];
        }

        $fields = [];
        $taken = [];

        foreach ($submitted as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $type = ClientAttributeType::tryFrom((string) ($row['type'] ?? ''));

            if ($name === '' || ! $type instanceof ClientAttributeType) {
                continue;
            }

            $key = trim((string) ($row['key'] ?? '')) ?: self::keyFor($name);

            // A row whose name collides with one above it would overwrite its answers.
            if ($key === '' || in_array($key, $taken, true)) {
                continue;
            }

            $taken[] = $key;

            $was = $this->existingField($existing, $key);

            $fields[] = new ClientAttributeField(
                key: $key,
                name: $name,
                type: $type,
                isRequired: (bool) ($row['is_required'] ?? false),
                choices: $type->usesChoices()
                    ? $this->mergedChoices($was?->choices, (string) ($row['options'] ?? ''))
                    : new ClientAttributeChoices,
                fields: $type->usesFields()
                    ? $this->parseFields(
                        is_array($row['fields'] ?? null) ? $row['fields'] : [],
                        $was?->fields ?? [],
                        $level + 1,
                    )
                    : [],
            );
        }

        return $fields;
    }

    /**
     * @param  list<ClientAttributeField>  $existing
     */
    private function existingField(array $existing, string $key): ?ClientAttributeField
    {
        foreach ($existing as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }

        return null;
    }

    private function mergedChoices(?ClientAttributeChoices $existing, string $text): ClientAttributeChoices
    {
        return ($existing ?? new ClientAttributeChoices)->mergeLines($text);
    }

    private function submittedType(): ?ClientAttributeType
    {
        $existing = $this->definition();

        if ($existing instanceof ClientAttribute) {
            return $existing->type;
        }

        return ClientAttributeType::tryFrom($this->string('type')->toString());
    }
}
