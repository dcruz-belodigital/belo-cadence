@props([
    'template',
    'action',
    'templateUrl',
    'cancelUrl',
    'submitLabel',
    'notes' => null,
])

{{--
    Step one of an import. The file comes first, because taking a file is what this page
    is for; the column reference sits underneath as something to consult, and the
    explanation of the whole process lives in the user guide rather than being repeated
    on top of every import page.
--}}
<div class="space-y-6">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf

        <x-card :title="__('imports.fields.file')" :description="__('imports.upload.hint')">
            <x-form.field name="file" :label="__('imports.fields.file')" required>
                <x-form.input name="file" type="file" accept=".csv,text/csv" required />
            </x-form.field>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-5">
                {{-- A starting point is an aside rather than an action, so it is offered as a link. --}}
                <p class="text-meta text-foreground-muted">
                    {{ __('imports.upload.starting_point') }}
                    <a href="{{ $templateUrl }}"
                       class="focus-ring rounded-control font-medium text-primary underline underline-offset-2 transition hover:text-primary-hover">
                        {{ __('imports.upload.template_link') }}
                    </a>
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <x-button :href="$cancelUrl" variant="secondary" type="button">
                        {{ __('common.actions.cancel') }}
                    </x-button>

                    <x-button type="submit" icon-after="chevron-right">{{ $submitLabel }}</x-button>
                </div>
            </div>
        </x-card>
    </form>

    {{--
        The card keeps its padding and the table keeps its own frame, so the two borders
        sit one inside the other instead of colliding at the card's edge.
    --}}
    <x-card :title="__('imports.available_columns')" :description="__('imports.available_columns_hint')">
        <x-table :label="__('imports.available_columns')">
            <x-slot:head>
                <x-table.heading>{{ __('imports.columns.column') }}</x-table.heading>
                <x-table.heading>{{ __('imports.columns.requirement') }}</x-table.heading>
                <x-table.heading>{{ __('imports.columns.example') }}</x-table.heading>
            </x-slot:head>

            @foreach ($template->columns as $column)
                <x-table.row>
                    <x-table.cell>
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-medium text-foreground">{{ $column->label }}</span>
                            <code class="rounded border border-border bg-surface-muted px-1.5 py-0.5 font-mono text-meta">{{ $column->name }}</code>
                        </span>
                    </x-table.cell>

                    <x-table.cell>
                        <x-badge :variant="$column->required ? 'danger' : 'neutral'">
                            {{ $column->required ? __('imports.columns.required') : __('imports.columns.optional') }}
                        </x-badge>
                    </x-table.cell>

                    <x-table.cell muted>
                        {{ $column->example === '' ? __('common.placeholders.none') : $column->example }}
                    </x-table.cell>
                </x-table.row>
            @endforeach
        </x-table>

        {{-- Notes about particular columns belong with the columns, not above the file field. --}}
        @if ($notes)
            <ul class="mt-4 list-inside list-disc space-y-1 text-meta text-foreground-muted">
                {{ $notes }}
            </ul>
        @endif
    </x-card>
</div>
