@php
    use App\Enums\ClientAttributeType;
    use App\Models\ClientAttribute;
    use App\Models\ClientAttributeFile;
    use App\ValueObjects\ClientAttributeField;
    use App\ValueObjects\StoredFile;

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

    /*
    | The names of the files this client holds, so a form coming back from a failure can
    | still say what a kept file is called — old input carries the id but not the name.
    | Empty while a client is being created, which holds nothing yet.
    */
    $heldFileNames = ($client ?? null)?->attributeFiles
        ?->pluck('original_name', 'id')
        ?->all() ?? [];

    /*
    | The download link with its id left as a placeholder, because the rows a file can sit
    | in are drawn by the browser. The route is still what builds it, so the path is
    | written in exactly one place.
    */
    $fileUrlTemplate = route('clients.files.show', ['file' => '__file__']);

    /**
     * What a file cell looks like to the browser, from a stored answer or from old input.
     *
     * A stored answer is a reference to an uploaded file; old input is what the form last
     * submitted, which carries the id under `keep` and nothing else worth keeping. Either
     * way the browser only ever holds the id and the name.
     */
    $fileCell = function (mixed $value) use (&$heldFileNames): array {
        $stored = StoredFile::fromValue($value);

        if ($stored !== null) {
            return ['keep' => (string) $stored->id, 'name' => $stored->name];
        }

        $keep = is_array($value) ? ($value['keep'] ?? null) : null;

        return is_numeric($keep)
            ? ['keep' => (string) (int) $keep, 'name' => $heldFileNames[(int) $keep] ?? '']
            : ['keep' => '', 'name' => ''];
    };

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
                    /*
                    | A file cell holds the id of the file this row already has and the name
                    | to show for it — never the bytes, which no script can hold. Old input
                    | arrives as the form submitted it, so both shapes are read the same way.
                    */
                    $field->type->usesFile() => $fileCell($cell),
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
                @elseif ($attribute->type->usesFile())
                    @php
                        $file = $fileCell($current);
                        // What may be uploaded is always worth saying, so it joins whatever
                        // the attribute says about itself rather than stacking beneath it.
                        $fileHint = trim(($attribute->hint ? $attribute->hint.' ' : '').__('client_attributes.hints.file', [
                            'size' => ClientAttributeFile::MAX_KILOBYTES / 1024,
                            'types' => implode(', ', ClientAttributeFile::EXTENSIONS),
                        ]));
                    @endphp

                    <x-form.field :name="$field.'[file]'"
                                  :error-key="$errorKey"
                                  :input-id="$inputId"
                                  :label="$attribute->name"
                                  :hint="$fileHint"
                                  :required="$attribute->is_required">
                        <div class="space-y-2" x-data="{ keep: @js($file['keep']), name: @js($file['name']) }">
                            {{-- Which file to keep when no new one is chosen. Cleared by "remove". --}}
                            <input type="hidden" name="{{ $field }}[keep]" x-model="keep">

                            <div class="flex flex-wrap items-center gap-3" x-show="keep !== ''" x-cloak>
                                <x-icon name="document" size="size-4" class="shrink-0 text-foreground-subtle" />

                                <a class="focus-ring truncate rounded-control text-body underline decoration-border-strong underline-offset-4"
                                   x-bind:href="@js($fileUrlTemplate).replace('__file__', keep)"
                                   x-text="name"></a>

                                <button type="button"
                                        class="btn btn-ghost btn-sm text-danger"
                                        x-on:click="keep = ''; name = ''">
                                    {{ __('client_attributes.actions.remove_file') }}
                                </button>
                            </div>

                            <x-form.input :name="$field.'[file]'" :id="$inputId" :error-key="$errorKey" type="file" />
                        </div>
                    </x-form.field>
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
