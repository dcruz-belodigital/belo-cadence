@php
    use App\Enums\ClientStatus;
    use App\Models\Client;
@endphp

<x-app-layout :heading="__('clients.title')">
    <x-page-header :description="__('clients.description')">
        <x-slot:actions>
            @can('export', Client::class)
                <x-export-menu :action="route('clients.export')" />
            @endcan

            @can('import', Client::class)
                <x-button :href="route('clients.import.create')" variant="secondary" icon="upload">
                    {{ __('common.actions.import') }}
                </x-button>
            @endcan

            @can('create', Client::class)
                <x-button :href="route('clients.create')" icon="plus">{{ __('clients.actions.create') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :action="route('clients.index')" :active="$filters->isActive()">
        <x-form.field name="search" :label="__('clients.filters.search')">
            <x-form.input name="search" type="search" :value="$filters->search" :placeholder="__('common.placeholders.search')" />
        </x-form.field>

        <x-form.field name="status" :label="__('clients.filters.status')">
            <x-form.select name="status"
                           :options="collect(ClientStatus::cases())->mapWithKeys(fn (ClientStatus $status) => [$status->value => $status->label()])"
                           :selected="$filters->status?->value"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <div class="flex items-end pb-2">
            <x-form.checkbox name="archived" :label="__('clients.filters.archived')" :checked="$filters->includeArchived" value="1" />
        </div>
    </x-filter-bar>

    <x-table :label="__('clients.title')">
        <x-slot:head>
            <x-table.sort-heading column="name">{{ __('clients.columns.name') }}</x-table.sort-heading>
            <x-table.sort-heading column="email">{{ __('clients.columns.email') }}</x-table.sort-heading>
            <x-table.sort-heading column="status">{{ __('clients.columns.status') }}</x-table.sort-heading>
            <x-table.heading align="right">{{ __('clients.columns.schedules') }}</x-table.heading>
            <x-table.sort-heading column="created_at">{{ __('clients.columns.created') }}</x-table.sort-heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.actions.view') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($clients as $client)
            <x-table.row>
                <x-table.cell>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('clients.show', $client) }}" class="focus-ring rounded-control font-medium text-foreground transition hover:text-primary">
                            {{ $client->name }}
                        </a>

                        @if ($client->trashed())
                            <x-badge variant="neutral">{{ __('clients.filters.archived') }}</x-badge>
                        @endif
                    </div>
                </x-table.cell>

                <x-table.cell muted>{{ $client->email }}</x-table.cell>

                <x-table.cell>
                    <x-badge :variant="$client->status->badgeVariant()">{{ $client->status->label() }}</x-badge>
                </x-table.cell>

                <x-table.cell align="right" muted>{{ $client->notification_schedules_count }}</x-table.cell>

                <x-table.cell muted><x-datetime :value="$client->created_at" format="date" /></x-table.cell>

                <x-table.cell align="right">
                    <div class="flex items-center justify-end gap-1">
                        <x-button :href="route('clients.show', $client)" variant="ghost" size="sm" icon="eye">
                            {{ __('common.actions.view') }}
                        </x-button>

                        @can('update', $client)
                            @unless ($client->trashed())
                                <x-button :href="route('clients.edit', $client)" variant="ghost" size="sm" icon="pencil">
                                    {{ __('common.actions.edit') }}
                                </x-button>
                            @endunless
                        @endcan
                    </div>
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="6">
                    <x-empty-state :title="$filters->isActive() ? __('clients.empty.filtered_title') : __('clients.empty.title')"
                                   :description="$filters->isActive() ? __('clients.empty.filtered_description') : __('clients.empty.description')"
                                   icon="building">
                        @can('create', Client::class)
                            @unless ($filters->isActive())
                                <x-slot:actions>
                                    <x-button :href="route('clients.create')" icon="plus">{{ __('clients.actions.create') }}</x-button>
                                </x-slot:actions>
                            @endunless
                        @endcan
                    </x-empty-state>
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$clients" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
