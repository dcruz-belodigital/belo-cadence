@php
    use App\Enums\ClientAttributeType;
    use App\Models\ClientAttribute;
    use App\ValueObjects\ClientAttributeField;

    /*
    | The custom attributes half of the client form.
    |
    | `$clientAttributes` are the active definitions in their chosen order, and
    | `$clientAttributeValues` is what this client has already answered, keyed by
    | definition id. Both come from the controller; nothing is looked up here.
    |
    | Every active attribute is rendered, so the form always submits all of them — which
    | is what lets an answer be cleared rather than merely left alone.
    */
    $clientAttributes ??= collect();
    $clientAttributeValues ??= [];

    /**
     * Fills in every cell of every row, at every depth, so Alpine never binds to a key
     * that is not there — and normalises booleans, because old input arrives as strings
     * and "0" is truthy in Javascript.
     */
    $fillRows = function (mixed $rows, array $fields) use (&$fillRows): array {
        $filled = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = [];

            foreach ($fields as $field) {
                $cell = $row[$field->key] ?? null;

                $cells[$field->key] = match (true) {
                    $field->type->usesFields() => $fillRows($cell, $field->fields),
                    $field->type === ClientAttributeType::Boolean => filter_var($cell ?? false, FILTER_VALIDATE_BOOLEAN),
                    default => (string) ($cell ?? ''),
                };
            }

            $filled[] = $cells;
        }

        return $filled;
    };
@endphp

@if ($clientAttributes->isNotEmpty())
    <x-card :title="__('client_attributes.client.title')" :description="__('client_attributes.client.description')">
        <div class="space-y-5">
            @foreach ($clientAttributes as $attribute)
                @php
                    $field = 'client_attributes['.$attribute->getKey().']';
                    $errorKey = 'client_attributes.'.$attribute->getKey();
                    $inputId = 'client-attribute-'.$attribute->getKey();
                    $stored = $clientAttributeValues[$attribute->getKey()] ?? null;
                    $current = old($errorKey, $stored);
                @endphp

                @if ($attribute->type->usesFields())
                    <div class="space-y-3"
                         x-data="{ rows: {{ Js::from($fillRows($current, $attribute->fields)) }} }">
                        <div>
                            <x-form.label :required="$attribute->is_required">{{ $attribute->name }}</x-form.label>

                            @if ($attribute->hint)
                                <p class="mt-1 text-meta text-foreground-subtle">{{ $attribute->hint }}</p>
                            @endif
                        </div>

                        {{-- The rows are drawn by the browser, so their messages have nowhere of their own. --}}
                        <x-form.error :name="$errorKey" :id="$inputId" />

                        @include('clients.partials.attribute-rows', [
                            'fields' => $attribute->fields,
                            'collection' => 'rows',
                            'prefix' => "'".$field."'",
                            'level' => 1,
                        ])
                    </div>
                @elseif ($attribute->type === ClientAttributeType::Boolean)
                    <x-form.checkbox :name="$field"
                                     :error-key="$errorKey"
                                     :id="$inputId"
                                     :label="$attribute->name"
                                     :hint="$attribute->hint"
                                     :checked="filter_var($current ?? false, FILTER_VALIDATE_BOOLEAN)" />
                @else
                    <x-form.field :name="$field"
                                  :error-key="$errorKey"
                                  :input-id="$inputId"
                                  :label="$attribute->name"
                                  :hint="$attribute->hint"
                                  :required="$attribute->is_required">
                        @switch ($attribute->type)
                            @case (ClientAttributeType::LongText)
                                <x-form.textarea :name="$field" :id="$inputId" :error-key="$errorKey" rows="4" maxlength="5000">{{ $current }}</x-form.textarea>
                                @break

                            @case (ClientAttributeType::Select)
                                <x-form.select :name="$field"
                                               :id="$inputId"
                                               :error-key="$errorKey"
                                               :options="collect($attribute->options->choices)->mapWithKeys(fn ($choice) => [
                                                   $choice->value => $choice->isActive
                                                       ? $choice->value
                                                       : $choice->value.' — '.__('client_attributes.show.retired'),
                                               ])"
                                               :selected="$current"
                                               :placeholder="__('common.placeholders.none')" />
                                @break

                            @default
                                <x-form.input :name="$field"
                                              :id="$inputId"
                                              :error-key="$errorKey"
                                              :type="$attribute->type->inputType()"
                                              :value="$current" />
                        @endswitch
                    </x-form.field>
                @endif
            @endforeach
        </div>
    </x-card>
@endif
