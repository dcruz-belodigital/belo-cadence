@php
    use App\Enums\ClientAttributeType;
    use App\ValueObjects\ClientAttributeField;

    /** @var \App\Models\ClientAttribute|null $attribute */
    $attribute ??= null;
    $nextPosition ??= 0;

    /*
    | The rows Alpine starts with, from old input when the form is coming back and from
    | the stored definition otherwise. Booleans are normalised in PHP because old input
    | arrives as strings and "0" is truthy in Javascript — the same trap the default
    | notifications repeater records.
    */
    $rowsFromInput = function (array $rows) use (&$rowsFromInput): array {
        $normalised = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalised[] = [
                'key' => (string) ($row['key'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'type' => (string) ($row['type'] ?? ClientAttributeType::Text->value),
                'is_required' => filter_var($row['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'options' => (string) ($row['options'] ?? ''),
                'fields' => $rowsFromInput(is_array($row['fields'] ?? null) ? $row['fields'] : []),
            ];
        }

        return $normalised;
    };

    $rowsFromFields = function (array $fields) use (&$rowsFromFields): array {
        return array_map(fn (ClientAttributeField $field): array => [
            'key' => $field->key,
            'name' => $field->name,
            'type' => $field->type->value,
            'is_required' => $field->isRequired,
            'options' => $field->choices->toLines(),
            'fields' => $rowsFromFields($field->fields),
        ], $fields);
    };

    $fieldRows = is_array(old('fields'))
        ? $rowsFromInput(old('fields'))
        : $rowsFromFields($attribute?->fields ?? []);

    $currentType = old('type', $attribute?->type?->value ?? ClientAttributeType::Text->value);

    $typeOptions = collect(ClientAttributeType::cases())
        ->mapWithKeys(fn (ClientAttributeType $type) => [$type->value => $type->label()]);
@endphp

<div class="space-y-5" x-data="{ type: '{{ $currentType }}', fields: {{ Js::from($fieldRows) }} }">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field name="name" :label="__('client_attributes.fields.name')" :hint="__('client_attributes.hints.name')" required class="sm:col-span-2">
            <x-form.input name="name" :value="old('name', $attribute?->name)" required autofocus maxlength="255" />
        </x-form.field>

        @if ($attribute === null)
            <x-form.field name="type" :label="__('client_attributes.fields.type')" :hint="__('client_attributes.hints.type')" required>
                <x-form.select name="type" :options="$typeOptions" :selected="$currentType" x-model="type" />
            </x-form.field>
        @else
            {{-- Settled when the attribute was created: every answer already recorded is stored in this shape. --}}
            <x-form.field :label="__('client_attributes.fields.type')" :hint="__('client_attributes.hints.type')">
                <p class="form-control bg-surface-sunken text-foreground-muted">{{ $attribute->type->label() }}</p>
            </x-form.field>

            <x-form.field :label="__('client_attributes.fields.key')" :hint="__('client_attributes.hints.key')">
                <p class="form-control bg-surface-sunken font-mono text-foreground-muted">{{ $attribute->key }}</p>
            </x-form.field>
        @endif

        <x-form.field name="position" :label="__('client_attributes.fields.position')" :hint="__('client_attributes.hints.position')" required>
            <x-form.input name="position" type="number" min="0" max="9999" :value="old('position', $attribute?->position ?? $nextPosition)" required />
        </x-form.field>

        <x-form.field name="hint" :label="__('client_attributes.fields.hint')" :hint="__('client_attributes.hints.hint')" class="sm:col-span-2">
            <x-form.input name="hint" :value="old('hint', $attribute?->hint)" maxlength="500" />
        </x-form.field>
    </div>

    <div x-show="type === '{{ ClientAttributeType::Select->value }}'" x-cloak>
        <x-form.field name="options" :label="__('client_attributes.fields.options')" :hint="__('client_attributes.hints.options')">
            <x-form.textarea name="options" rows="5">{{ old('options', $attribute?->options?->toLines()) }}</x-form.textarea>
        </x-form.field>
    </div>

    <div x-show="type === '{{ ClientAttributeType::Repeater->value }}'" x-cloak class="space-y-3">
        <div>
            <x-form.label>{{ __('client_attributes.fields.fields') }}</x-form.label>
            <p class="mt-1 text-meta text-foreground-subtle">{{ __('client_attributes.hints.fields') }}</p>
        </div>

        {{-- The rows are drawn by the browser, so their messages cannot sit under a field. --}}
        <x-form.error :name="['fields', 'fields.*']" />

        @include('admin.client-attributes.partials.sub-fields', [
            'level' => 1,
            'collection' => 'fields',
            'prefix' => "'fields'",
        ])
    </div>

    <div class="space-y-3">
        <x-form.checkbox name="is_required"
                         :label="__('client_attributes.fields.is_required')"
                         :hint="__('client_attributes.hints.is_required')"
                         :checked="old('is_required', $attribute?->is_required ?? false)" />

        <x-form.checkbox name="is_active"
                         :label="__('client_attributes.fields.is_active')"
                         :hint="__('client_attributes.hints.is_active')"
                         :checked="old('is_active', $attribute?->is_active ?? true)" />
    </div>
</div>
