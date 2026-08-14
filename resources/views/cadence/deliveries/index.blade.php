@php
    use App\Enums\ClientEmailTemplate;
    use App\Enums\ClientNotificationDeliveryStatus;
    use App\Models\ClientNotificationDelivery;
@endphp

<x-app-layout :heading="__('deliveries.title')">
    <x-page-header :description="__('deliveries.description')">
        <x-slot:actions>
            @can('export', ClientNotificationDelivery::class)
                <x-export-menu :action="route('cadence.deliveries.export')" />
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-filter-bar :action="route('cadence.deliveries.index')" :active="$filters->isActive()">
        <x-form.field name="search" :label="__('deliveries.filters.search')">
            <x-form.input name="search" type="search" :value="$filters->search" :placeholder="__('common.placeholders.search')" />
        </x-form.field>

        <x-form.field name="client" :label="__('deliveries.filters.client')">
            <x-form.select name="client" :options="$clients" :selected="$filters->clientId" :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <x-form.field name="template" :label="__('deliveries.filters.template')">
            <x-form.select name="template"
                           :options="collect(ClientEmailTemplate::cases())->mapWithKeys(fn (ClientEmailTemplate $template) => [$template->value => $template->label()])"
                           :selected="$filters->template?->value"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <x-form.field name="status" :label="__('deliveries.filters.status')">
            <x-form.select name="status"
                           :options="collect(ClientNotificationDeliveryStatus::cases())->mapWithKeys(fn (ClientNotificationDeliveryStatus $status) => [$status->value => $status->label()])"
                           :selected="$filters->status?->value"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>

        <x-form.field name="from" :label="__('deliveries.filters.from')">
            <x-form.input name="from" type="date" :value="$filters->fromDate" />
        </x-form.field>

        <x-form.field name="to" :label="__('deliveries.filters.to')">
            <x-form.input name="to" type="date" :value="$filters->toDate" />
        </x-form.field>
    </x-filter-bar>

    <x-table :label="__('deliveries.title')">
        <x-slot:head>
            <x-table.sort-heading column="scheduled_for">{{ __('deliveries.columns.scheduled_for') }}</x-table.sort-heading>
            <x-table.sort-heading column="client">{{ __('deliveries.columns.client') }}</x-table.sort-heading>
            <x-table.heading>{{ __('deliveries.columns.subject') }}</x-table.heading>
            <x-table.heading>{{ __('deliveries.columns.recipient') }}</x-table.heading>
            <x-table.sort-heading column="sent_at">{{ __('deliveries.columns.sent_at') }}</x-table.sort-heading>
            <x-table.sort-heading column="status">{{ __('deliveries.columns.status') }}</x-table.sort-heading>
            <x-table.heading align="right"><span class="sr-only">{{ __('common.actions.view') }}</span></x-table.heading>
        </x-slot:head>

        @forelse ($deliveries as $delivery)
            <x-table.row>
                <x-table.cell><x-datetime :value="$delivery->scheduled_for" /></x-table.cell>

                <x-table.cell>
                    <a href="{{ route('clients.show', $delivery->client) }}" class="focus-ring rounded-control font-medium transition hover:text-primary">
                        {{ $delivery->client->name }}
                    </a>
                </x-table.cell>

                <x-table.cell>{{ $delivery->subject }}</x-table.cell>
                <x-table.cell muted>{{ $delivery->recipient_email }}</x-table.cell>
                <x-table.cell muted><x-datetime :value="$delivery->sent_at" /></x-table.cell>

                <x-table.cell>
                    <x-badge :variant="$delivery->status->badgeVariant()">{{ $delivery->status->label() }}</x-badge>
                </x-table.cell>

                <x-table.cell align="right">
                    @can('view', $delivery)
                        <x-button :href="route('cadence.deliveries.show', $delivery)" variant="ghost" size="sm" icon="eye">
                            {{ __('common.actions.view') }}
                        </x-button>
                    @endcan
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row class="hover:bg-transparent">
                <x-table.cell colspan="7">
                    <x-empty-state :title="$filters->isActive() ? __('deliveries.empty.filtered_title') : __('deliveries.empty.title')"
                                   :description="$filters->isActive() ? __('deliveries.empty.filtered_description') : __('deliveries.empty.description')"
                                   icon="envelope" />
                </x-table.cell>
            </x-table.row>
        @endforelse

        <x-slot:footer>
            <x-pagination :paginator="$deliveries" />
        </x-slot:footer>
    </x-table>
</x-app-layout>
