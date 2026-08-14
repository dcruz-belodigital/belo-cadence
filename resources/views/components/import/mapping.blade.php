@props([
    'template',
    'action',
    'startOverUrl',
    'fileName',
    'headings',
    'mapping',
    'previewRows',
    'rowCount',
    'submitLabel',
])

{{--
    Step two of an import: which heading in the uploaded file provides each column. The
    matching is suggested by name and can be corrected before anything is written.
--}}
<div class="space-y-6">
    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <x-icon name="document" size="size-5" class="shrink-0 text-foreground-subtle" />

                <div class="min-w-0">
                    <p class="truncate text-label">{{ $fileName }}</p>
                    <p class="text-meta text-foreground-muted">
                        {{ trans_choice('imports.mapping.row_count', $rowCount, ['count' => $rowCount]) }}
                    </p>
                </div>
            </div>

            <x-button :href="$startOverUrl" variant="secondary" size="sm" icon="restore">
                {{ __('imports.mapping.start_over') }}
            </x-button>
        </div>
    </x-card>

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf

        <x-card :title="__('imports.mapping.title')" :description="__('imports.mapping.description')">
            {{-- A mapping that does not cover every required column fails as a whole, not per select. --}}
            <x-form.error :name="['mapping', 'mapping.*']" class="mb-4" />

            <div class="space-y-4">
                @foreach ($template->columns as $column)
                    @php
                        $field = 'mapping['.$column->name.']';
                        $selected = old('mapping.'.$column->name, $mapping->headingFor($column->name));
                    @endphp

                    <div class="grid gap-2 border-b border-border pb-4 last:border-0 last:pb-0 sm:grid-cols-2 sm:items-center sm:gap-4">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 text-label">
                                {{ $column->label }}

                                @if ($column->required)
                                    <x-badge variant="danger">{{ __('imports.columns.required') }}</x-badge>
                                @endif
                            </p>
                            <p class="mt-0.5 font-mono text-meta text-foreground-subtle">{{ $column->name }}</p>
                        </div>

                        <x-form.select :name="$field"
                                       :id="'mapping-'.$column->name"
                                       :options="collect($headings)->mapWithKeys(fn (string $heading) => [$heading => $heading])"
                                       :selected="$selected"
                                       :placeholder="__('imports.mapping.not_imported')" />
                    </div>
                @endforeach
            </div>
        </x-card>

        @if ($previewRows !== [])
            <x-card :title="__('imports.mapping.preview')" :description="__('imports.mapping.preview_hint')" flush>
                <x-table :label="__('imports.mapping.preview')">
                    <x-slot:head>
                        @foreach ($headings as $heading)
                            <x-table.heading>{{ $heading }}</x-table.heading>
                        @endforeach
                    </x-slot:head>

                    @foreach ($previewRows as $row)
                        <x-table.row>
                            @foreach ($headings as $heading)
                                <x-table.cell muted>
                                    {{ ($row[$heading] ?? '') === '' ? __('common.placeholders.none') : $row[$heading] }}
                                </x-table.cell>
                            @endforeach
                        </x-table.row>
                    @endforeach
                </x-table>
            </x-card>
        @endif

        <x-form.actions>
            <x-button :href="$startOverUrl" variant="secondary" type="button">
                {{ __('imports.mapping.start_over') }}
            </x-button>

            <x-button type="submit" icon="upload">{{ $submitLabel }}</x-button>
        </x-form.actions>
    </form>
</div>
