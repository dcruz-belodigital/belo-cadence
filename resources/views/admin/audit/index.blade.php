@php
    use App\Enums\AuditAction;
    use App\Models\Audit;
@endphp

<x-app-layout :heading="__('audit.title')">
    <x-page-header :description="__('audit.description')">
        <x-slot:actions>
            @can('export', Audit::class)
                <x-export-menu :action="route('admin.audit-log.export')" />
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :action="route('admin.audit-log.index')" :active="$filters->isActive()">
        <x-form.field name="search" :label="__('audit.filters.search')">
            <x-form.input name="search" type="search" :value="$filters->search" :placeholder="__('common.placeholders.search')" />
        </x-form.field>

        <x-form.field name="action" :label="__('audit.filters.action')">
            <x-form.select name="action"
                           :options="collect(AuditAction::cases())->mapWithKeys(fn (AuditAction $action) => [$action->value => $action->label()])"
                           :selected="$filters->action?->value"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <x-form.field name="type" :label="__('audit.filters.type')">
            <x-form.select name="type"
                           :options="$auditableTypes->mapWithKeys(fn (string $type) => [$type => __('audit.auditable_types.'.$type)])"
                           :selected="$filters->auditableType"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <x-form.field name="from" :label="__('audit.filters.from')">
            <x-form.input name="from" type="date" :value="$filters->fromDate" />
        </x-form.field>

        <x-form.field name="to" :label="__('audit.filters.to')">
            <x-form.input name="to" type="date" :value="$filters->toDate" />
        </x-form.field>
    </x-filter-bar>

    <x-table :label="__('audit.title')">
        <x-slot:head>
            <x-table.sort-heading column="created_at">{{ __('audit.columns.when') }}</x-table.sort-heading>
            <x-table.heading>{{ __('audit.columns.actor') }}</x-table.heading>
            <x-table.sort-heading column="action">{{ __('audit.columns.action') }}</x-table.sort-heading>
            <x-table.heading>{{ __('audit.columns.record') }}</x-table.heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($audits as $audit)
            <x-table.row>
                <x-table.cell><x-datetime :value="$audit->created_at" /></x-table.cell>

                <x-table.cell>
                    @if ($audit->user === null)
                        <span class="text-foreground-muted" title="{{ __('audit.system_actor_hint') }}">
                            {{ __('audit.system_actor') }}
                        </span>
                    @else
                        {{ $audit->user->name }}
                    @endif
                </x-table.cell>

                <x-table.cell>
                    <x-badge :variant="$audit->action->badgeVariant()">{{ $audit->action->label() }}</x-badge>
                </x-table.cell>

                <x-table.cell muted>
                    @if ($audit->auditable_type === null)
                        {{ __('common.placeholders.none') }}
                    @else
                        {{ $audit->auditableTypeLabel() }}@if ($audit->auditable_id !== null) #{{ $audit->auditable_id }}@endif
                    @endif
                </x-table.cell>

                <x-table.cell align="right">
                    @can('view', $audit)
                        <x-table.actions>
                            <x-dropdown.item :href="route('admin.audit-log.show', $audit)" icon="eye">
                                {{ __('common.actions.view') }}
                            </x-dropdown.item>
                        </x-table.actions>
                    @endcan
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="5">
                    <x-empty-state :title="$filters->isActive() ? __('audit.empty.filtered_title') : __('audit.empty.title')"
                                   :description="$filters->isActive() ? __('audit.empty.filtered_description') : __('audit.empty.description')"
                                   icon="clipboard" />
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$audits" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
