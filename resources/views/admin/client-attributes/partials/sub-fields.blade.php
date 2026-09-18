@php
    use App\Enums\ClientAttributeType;
    use App\Models\ClientAttribute;

    /*
    | One level of a repeater's field editor.
    |
    | The tree is being written here rather than read, so the browser has to draw it and
    | Alpine has no recursive component. This partial therefore includes itself once per
    | level up to ClientAttribute::MAX_DEPTH, which is why the cap exists at all.
    |
    | `$collection` and `$prefix` are Javascript expressions, not strings: each level
    | appends its own index to the name its parent built, so a field three levels down
    | still submits under one readable name.
    |
    | Labels wrap their input rather than pointing at an id, because an id would have to
    | be unique across every branch of a tree the server cannot see.
    */
    $row = 'row'.$level;
    $index = 'i'.$level;
    $nameFor = fn (string $field): string => "{$prefix} + '[' + {$index} + '][{$field}]'";

    $canNest = $level < ClientAttribute::MAX_DEPTH;

    $typeOptions = collect(ClientAttributeType::cases())
        ->reject(fn (ClientAttributeType $type) => $type->usesFields() && ! $canNest)
        ->mapWithKeys(fn (ClientAttributeType $type) => [$type->value => $type->label()]);
@endphp

<div class="space-y-3">
    <template x-for="({{ $row }}, {{ $index }}) in {{ $collection }}" x-bind:key="{{ $index }}">
        <div class="space-y-3 rounded-card border border-border bg-surface-sunken/40 p-3">
            <input type="hidden" x-bind:name="{{ $nameFor('key') }}" x-model="{{ $row }}.key">

            <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <label class="block space-y-1.5">
                    <span class="block text-label">{{ __('client_attributes.fields.field_name') }}</span>
                    <input type="text" class="form-control" maxlength="255"
                           x-bind:name="{{ $nameFor('name') }}"
                           x-model="{{ $row }}.name">
                </label>

                <label class="block space-y-1.5">
                    <span class="block text-label">{{ __('client_attributes.fields.field_type') }}</span>
                    <select class="form-control" x-bind:name="{{ $nameFor('type') }}" x-model="{{ $row }}.type">
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-center justify-between gap-4 pb-2 sm:justify-end">
                    <label class="flex items-center gap-2 text-body">
                        <input type="hidden" x-bind:name="{{ $nameFor('is_required') }}" value="0">
                        <input type="checkbox" value="1"
                               x-bind:name="{{ $nameFor('is_required') }}"
                               x-model="{{ $row }}.is_required"
                               class="focus-ring size-4 rounded border-border-strong bg-surface accent-primary">
                        {{ __('client_attributes.fields.field_required') }}
                    </label>

                    <button type="button" class="btn btn-ghost btn-sm text-danger" x-on:click="{{ $collection }}.splice({{ $index }}, 1)">
                        {{ __('client_attributes.actions.remove_field') }}
                    </button>
                </div>
            </div>

            <label class="block space-y-1.5" x-show="{{ $row }}.type === '{{ ClientAttributeType::Select->value }}'" x-cloak>
                <span class="block text-label">{{ __('client_attributes.fields.options') }}</span>
                <textarea rows="3" class="form-control"
                          x-bind:name="{{ $nameFor('options') }}"
                          x-model="{{ $row }}.options"></textarea>
                <span class="block text-meta text-foreground-subtle">{{ __('client_attributes.hints.options') }}</span>
            </label>

            @if ($canNest)
                <div x-show="{{ $row }}.type === '{{ ClientAttributeType::Repeater->value }}'" x-cloak class="space-y-3 border-l-2 border-border pl-3">
                    <p class="text-label">{{ __('client_attributes.fields.fields') }}</p>

                    @include('admin.client-attributes.partials.sub-fields', [
                        'level' => $level + 1,
                        'collection' => $row.'.fields',
                        'prefix' => $nameFor('fields'),
                    ])
                </div>
            @endif
        </div>
    </template>

    <div>
        <x-button type="button" variant="secondary" size="sm" icon="plus"
                  x-on:click="{{ $collection }}.push({ key: '', name: '', type: '{{ ClientAttributeType::Text->value }}', is_required: false, options: '', fields: [] })">
            {{ __('client_attributes.actions.add_field') }}
        </x-button>
    </div>
</div>
