@php
    use App\Enums\ClientAttributeType;
    use App\ValueObjects\ClientAttributeField;

    /*
    | One level of a repeater's rows on the client form.
    |
    | Unlike the definition editor, the field tree is already known here, so Blade writes
    | the cells once and the browser only ever clones rows. That is what lets this partial
    | include itself to any depth: `$collection` and `$prefix` are Javascript expressions,
    | and each level appends its own index to the name its parent built.
    */
    $row = 'r'.$level;
    $index = 'n'.$level;
    $nameFor = fn (string $key): string => "{$prefix} + '[' + {$index} + '][{$key}]'";
    $cellOf = fn (string $key): string => "{$row}['{$key}']";

    // See `attribute-fields`: the id is a placeholder because the rows are drawn here.
    $fileUrlTemplate = route('clients.files.show', ['file' => '__file__']);
@endphp

<div class="space-y-3">
    <template x-for="({{ $row }}, {{ $index }}) in {{ $collection }}" x-bind:key="{{ $index }}">
        <div class="space-y-3 rounded-card border border-border p-3">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($fields as $field)
                    @if ($field->type->usesFields())
                        <div class="space-y-2 border-l-2 border-border pl-3 sm:col-span-2">
                            @if ($field->isNamed())
                                <p class="text-label">{{ $field->name }}</p>
                            @endif

                            @include('clients.partials.attribute-rows', [
                                'fields' => $field->fields,
                                'collection' => $cellOf($field->key),
                                'prefix' => $nameFor($field->key),
                                'level' => $level + 1,
                            ])
                        </div>
                    @elseif ($field->type->usesFile())
                        {{--
                            A file cell submits two things: which file to keep, and a new
                            upload that replaces it. Nothing binds the upload itself — a
                            file input is the one control a script may not fill — so a
                            failed save loses the choice and keeps whatever was there.
                        --}}
                        <label class="block space-y-1.5">
                            @if ($field->isNamed() || $field->isRequired)
                                <span class="block text-label">
                                    {{ $field->name }}
                                    @if ($field->isRequired)
                                        <span class="text-danger" aria-hidden="true">*</span>
                                        <span class="sr-only">{{ __('common.form.required') }}</span>
                                    @endif
                                </span>
                            @endif

                            <input type="hidden" x-bind:name="{{ $nameFor($field->key)." + '[keep]'" }}" x-model="{{ $cellOf($field->key) }}.keep">

                            <div class="flex flex-wrap items-center gap-2" x-show="{{ $cellOf($field->key) }}.keep !== ''" x-cloak>
                                <x-icon name="document" size="size-4" class="shrink-0 text-foreground-subtle" />

                                <a class="focus-ring truncate rounded-control text-body underline decoration-border-strong underline-offset-4"
                                   x-bind:href="@js($fileUrlTemplate).replace('__file__', {{ $cellOf($field->key) }}.keep)"
                                   x-text="{{ $cellOf($field->key) }}.name"></a>

                                <button type="button"
                                        class="btn btn-ghost btn-sm text-danger"
                                        x-on:click="{{ $cellOf($field->key) }}.keep = ''; {{ $cellOf($field->key) }}.name = ''">
                                    {{ __('client_attributes.actions.remove_file') }}
                                </button>
                            </div>

                            <input type="file" class="form-file" x-bind:name="{{ $nameFor($field->key)." + '[file]'" }}">
                        </label>
                    @elseif ($field->type === ClientAttributeType::Boolean)
                        <label class="flex items-center gap-2.5 text-body">
                            {{-- The hidden twin means an unticked box still submits a value. --}}
                            <input type="hidden" x-bind:name="{{ $nameFor($field->key) }}" value="0">
                            <input type="checkbox" value="1"
                                   x-bind:name="{{ $nameFor($field->key) }}"
                                   x-model="{{ $cellOf($field->key) }}"
                                   class="focus-ring size-4 shrink-0 rounded border-border-strong bg-surface accent-primary">
                            {{ $field->name }}
                        </label>
                    @else
                        {{-- Labels wrap their input: an id would have to be unique across every branch. --}}
                        <label class="block space-y-1.5">
                            {{-- A field nobody named is a bare value, so it gets no label of its own. --}}
                            @if ($field->isNamed() || $field->isRequired)
                                <span class="block text-label">
                                    {{ $field->name }}
                                    @if ($field->isRequired)
                                        <span class="text-danger" aria-hidden="true">*</span>
                                        <span class="sr-only">{{ __('common.form.required') }}</span>
                                    @endif
                                </span>
                            @endif

                            @if ($field->type === ClientAttributeType::LongText)
                                <textarea rows="3" class="form-control"
                                          x-bind:name="{{ $nameFor($field->key) }}"
                                          x-model="{{ $cellOf($field->key) }}"></textarea>
                            @elseif ($field->type === ClientAttributeType::Select)
                                <select class="form-control"
                                        x-bind:name="{{ $nameFor($field->key) }}"
                                        x-model="{{ $cellOf($field->key) }}">
                                    <option value="">{{ __('common.placeholders.none') }}</option>
                                    @foreach ($field->choices->choices as $choice)
                                        <option value="{{ $choice->value }}">
                                            {{ $choice->value }}@unless ($choice->isActive) — {{ __('client_attributes.show.retired') }}@endunless
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="{{ $field->type->inputType() }}" class="form-control"
                                       x-bind:name="{{ $nameFor($field->key) }}"
                                       x-model="{{ $cellOf($field->key) }}">
                            @endif
                        </label>
                    @endif
                @endforeach
            </div>

            <div class="flex justify-end">
                <button type="button" class="btn btn-ghost btn-sm text-danger" x-on:click="{{ $collection }}.splice({{ $index }}, 1)">
                    {{ __('client_attributes.actions.remove_row') }}
                </button>
            </div>
        </div>
    </template>

    <template x-if="{{ $collection }}.length === 0">
        <p class="text-meta text-foreground-muted">{{ __('client_attributes.client.rows_empty') }}</p>
    </template>

    <div>
        <x-button type="button" variant="secondary" size="sm" icon="plus"
                  x-on:click="{{ $collection }}.push({{ Js::from(ClientAttributeField::blankRow($fields)) }})">
            {{ __('client_attributes.actions.add_row') }}
        </x-button>
    </div>
</div>
