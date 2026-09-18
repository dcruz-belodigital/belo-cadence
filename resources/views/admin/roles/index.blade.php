@php
    use App\Models\Role;
@endphp

<x-app-layout :heading="__('roles.title')">
    <x-page-header :description="__('roles.description')">
        <x-slot:actions>
            @can('export', Role::class)
                <x-export-menu :action="route('admin.roles.export')" />
            @endcan

            @can('create', Role::class)
                <x-button :href="route('admin.roles.create')" icon="plus">{{ __('roles.actions.create') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-table :label="__('roles.title')">
        <x-slot:head>
            <x-table.heading>{{ __('roles.columns.name') }}</x-table.heading>
            <x-table.heading align="right">{{ __('roles.columns.permissions') }}</x-table.heading>
            <x-table.heading align="right">{{ __('roles.columns.users') }}</x-table.heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($roles as $role)
            <x-table.row>
                <x-table.cell>
                    <a href="{{ route('admin.roles.show', $role) }}" class="focus-ring rounded-control font-medium transition hover:text-primary">
                        {{ $role->name }}
                    </a>
                </x-table.cell>

                <x-table.cell align="right" muted>{{ $role->permissions_count }}</x-table.cell>
                <x-table.cell align="right" muted>{{ $role->users_count }}</x-table.cell>

                <x-table.cell align="right">
                    <x-table.actions>
                        <x-dropdown.item :href="route('admin.roles.show', $role)" icon="eye">
                            {{ __('common.actions.view') }}
                        </x-dropdown.item>

                        @can('update', $role)
                            <x-dropdown.item :href="route('admin.roles.edit', $role)" icon="pencil">
                                {{ __('common.actions.edit') }}
                            </x-dropdown.item>
                        @endcan
                    </x-table.actions>
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="4">
                    <x-empty-state :title="__('roles.empty.title')" :description="__('roles.empty.description')" icon="shield">
                        @can('create', Role::class)
                            <x-slot:actions>
                                <x-button :href="route('admin.roles.create')" icon="plus">{{ __('roles.actions.create') }}</x-button>
                            </x-slot:actions>
                        @endcan
                    </x-empty-state>
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$roles" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
