@php
    use App\Enums\ClientAttributeType;
    use App\Models\ClientAttribute;
@endphp

<x-app-layout :heading="__('client_attributes.title')">
    <x-page-header :description="__('client_attributes.description')">
        <x-slot:actions>
            @can('create', ClientAttribute::class)
                <x-button :href="route('admin.client-attributes.create')" icon="plus">
                    {{ __('client_attributes.actions.create') }}
                </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :action="route('admin.client-attributes.index')" :active="$filters->isActive()">
        <x-form.field name="search" :label="__('client_attributes.filters.search')">
            <x-form.input name="search" type="search" :value="$filters->search" :placeholder="__('common.placeholders.search')" />
        </x-form.field>

        <x-form.field name="type" :label="__('client_attributes.filters.type')">
            <x-form.select name="type"
                           :options="collect(ClientAttributeType::cases())->mapWithKeys(fn (ClientAttributeType $type) => [$type->value => $type->label()])"
                           :selected="$filters->type?->value"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <x-form.field name="state" :label="__('client_attributes.filters.state')">
            <x-form.select name="state"
                           :options="['active' => __('client_attributes.states.active'), 'inactive' => __('client_attributes.states.inactive')]"
                           :selected="$filters->state()"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>
    </x-filter-bar>

    <x-table :label="__('client_attributes.title')">
        <x-slot:head>
            <x-table.sort-heading column="position">{{ __('client_attributes.columns.position') }}</x-table.sort-heading>
            <x-table.sort-heading column="name">{{ __('client_attributes.columns.name') }}</x-table.sort-heading>
            <x-table.sort-heading column="type">{{ __('client_attributes.columns.type') }}</x-table.sort-heading>
            <x-table.heading>{{ __('client_attributes.columns.required') }}</x-table.heading>
            <x-table.heading>{{ __('client_attributes.columns.state') }}</x-table.heading>
            <x-table.heading align="right">{{ __('client_attributes.columns.clients') }}</x-table.heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($attributes as $attribute)
            <x-table.row>
                <x-table.cell muted><span class="numeric">{{ $attribute->position }}</span></x-table.cell>

                <x-table.cell>
                    <a href="{{ route('admin.client-attributes.show', $attribute) }}" class="focus-ring rounded-control font-medium transition hover:text-primary">
                        {{ $attribute->name }}
                    </a>
                    {{-- Shown on purpose: it is the CSV column name somebody's file has to use. --}}
                    <span class="block font-mono text-meta text-foreground-subtle">{{ $attribute->key }}</span>
                </x-table.cell>

                <x-table.cell muted>{{ $attribute->type->label() }}</x-table.cell>

                <x-table.cell muted>{{ $attribute->is_required ? __('common.yes') : __('common.no') }}</x-table.cell>

                <x-table.cell>
                    <x-badge :variant="$attribute->is_active ? 'success' : 'neutral'">
                        {{ $attribute->is_active ? __('client_attributes.states.active') : __('client_attributes.states.inactive') }}
                    </x-badge>
                </x-table.cell>

                <x-table.cell align="right" muted><span class="numeric">{{ $attribute->values_count }}</span></x-table.cell>

                <x-table.cell align="right">
                    <x-table.actions>
                        <x-dropdown.item :href="route('admin.client-attributes.show', $attribute)" icon="eye">
                            {{ __('common.actions.view') }}
                        </x-dropdown.item>

                        @can('update', $attribute)
                            <x-dropdown.item :href="route('admin.client-attributes.edit', $attribute)" icon="pencil">
                                {{ __('common.actions.edit') }}
                            </x-dropdown.item>
                        @endcan

                        @can('delete', $attribute)
                            <x-confirm-form :action="route('admin.client-attributes.destroy', $attribute)"
                                            method="DELETE"
                                            trigger-as="menu-item"
                                            :title="__('client_attributes.delete.title')"
                                            :message="__('client_attributes.delete.message', ['count' => $attribute->values_count])"
                                            :confirm="__('client_attributes.delete.confirm')"
                                            icon="trash">
                                <x-slot:trigger>{{ __('client_attributes.actions.delete') }}</x-slot:trigger>
                            </x-confirm-form>
                        @endcan
                    </x-table.actions>
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="7">
                    <x-empty-state :title="$filters->isActive() ? __('client_attributes.empty.filtered_title') : __('client_attributes.empty.title')"
                                   :description="$filters->isActive() ? __('client_attributes.empty.filtered_description') : __('client_attributes.empty.description')"
                                   icon="key">
                        @can('create', ClientAttribute::class)
                            @unless ($filters->isActive())
                                <x-slot:actions>
                                    <x-button :href="route('admin.client-attributes.create')" icon="plus">
                                        {{ __('client_attributes.actions.create') }}
                                    </x-button>
                                </x-slot:actions>
                            @endunless
                        @endcan
                    </x-empty-state>
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$attributes" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
