@php
    use App\Models\User;
@endphp

<x-app-layout :heading="__('users.title')">
    <x-page-header :description="__('users.description')">
        <x-slot:actions>
            @can('export', User::class)
                <x-export-menu :action="route('admin.users.export')" />
            @endcan

            @can('import', User::class)
                <x-button :href="route('admin.users.import.create')" variant="secondary" icon="upload">
                    {{ __('common.actions.import') }}
                </x-button>
            @endcan

            @can('create', User::class)
                <x-button :href="route('admin.users.create')" icon="plus">{{ __('users.actions.create') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :action="route('admin.users.index')" :active="$filters->isActive()">
        <x-form.field name="search" :label="__('users.filters.search')">
            <x-form.input name="search" type="search" :value="$filters->search" :placeholder="__('common.placeholders.search')" />
        </x-form.field>

        <x-form.field name="role" :label="__('users.filters.role')">
            <x-form.select name="role" :options="$roles" :selected="$filters->role" :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <x-form.field name="state" :label="__('users.filters.state')">
            <x-form.select name="state"
                           :options="['active' => __('users.states.active'), 'inactive' => __('users.states.inactive')]"
                           :selected="$filters->state()"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>
    </x-filter-bar>

    <x-table :label="__('users.title')">
        <x-slot:head>
            <x-table.sort-heading column="name">{{ __('users.columns.name') }}</x-table.sort-heading>
            <x-table.sort-heading column="email">{{ __('users.columns.email') }}</x-table.sort-heading>
            <x-table.heading>{{ __('users.columns.roles') }}</x-table.heading>
            <x-table.heading>{{ __('users.columns.state') }}</x-table.heading>
            <x-table.sort-heading column="created_at">{{ __('users.columns.created') }}</x-table.sort-heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.columns.actions') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($users as $user)
            <x-table.row>
                <x-table.cell>
                    <a href="{{ route('admin.users.show', $user) }}" class="focus-ring rounded-control font-medium transition hover:text-primary">
                        {{ $user->name }}
                    </a>
                </x-table.cell>

                <x-table.cell muted>{{ $user->email }}</x-table.cell>

                <x-table.cell>
                    @forelse ($user->roles as $role)
                        <x-badge variant="info" class="mr-1">{{ $role->name }}</x-badge>
                    @empty
                        <span class="text-foreground-subtle">{{ __('common.placeholders.none') }}</span>
                    @endforelse
                </x-table.cell>

                <x-table.cell>
                    <x-badge :variant="$user->is_active ? 'success' : 'neutral'">
                        {{ $user->is_active ? __('users.states.active') : __('users.states.inactive') }}
                    </x-badge>
                </x-table.cell>

                <x-table.cell muted><x-datetime :value="$user->created_at" format="date" /></x-table.cell>

                <x-table.cell align="right">
                    <x-table.actions>
                        <x-dropdown.item :href="route('admin.users.show', $user)" icon="eye">
                            {{ __('common.actions.view') }}
                        </x-dropdown.item>

                        @can('update', $user)
                            <x-dropdown.item :href="route('admin.users.edit', $user)" icon="pencil">
                                {{ __('common.actions.edit') }}
                            </x-dropdown.item>
                        @endcan
                    </x-table.actions>
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="6">
                    <x-empty-state :title="__('users.empty.title')" :description="__('users.empty.description')" icon="users" />
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$users" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
