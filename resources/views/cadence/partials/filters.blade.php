@php
    use App\Enums\ClientEmailTemplate;
    use App\Enums\ClientNotificationFrequency;
    use App\Enums\NotificationTimeRange;

    $withRange ??= false;
@endphp

<x-filter-bar :action="$action" :active="$filters->isActive()">
    <x-form.field name="search" :label="__('cadence.filters.search')">
        <x-form.input name="search" type="search" :value="$filters->search" :placeholder="__('common.placeholders.search')" />
    </x-form.field>

    <x-form.field name="template" :label="__('cadence.filters.template')">
        <x-form.select name="template"
                       :options="collect(ClientEmailTemplate::cases())->mapWithKeys(fn (ClientEmailTemplate $template) => [$template->value => $template->label()])"
                       :selected="$filters->template?->value"
                       :placeholder="__('common.placeholders.all')" />
    </x-form.field>

    <x-form.field name="frequency" :label="__('cadence.filters.frequency')">
        <x-form.select name="frequency"
                       :options="collect(ClientNotificationFrequency::cases())->mapWithKeys(fn (ClientNotificationFrequency $frequency) => [$frequency->value => $frequency->label()])"
                       :selected="$filters->frequency?->value"
                       :placeholder="__('common.placeholders.all')" />
    </x-form.field>

    <x-form.field name="state" :label="__('cadence.filters.state')">
        <x-form.select name="state"
                       :options="['enabled' => __('cadence.states.enabled'), 'disabled' => __('cadence.states.disabled')]"
                       :selected="$filters->state()"
                       :placeholder="__('common.placeholders.all')" />
    </x-form.field>

    @if ($withRange)
        <x-form.field name="range" :label="__('cadence.filters.range')">
            <x-form.select name="range"
                           :options="collect(NotificationTimeRange::cases())->mapWithKeys(fn (NotificationTimeRange $range) => [$range->value => $range->label()])"
                           :selected="$filters->range?->value"
                           :placeholder="__('common.placeholders.all')" />
        </x-form.field>
    @endif
</x-filter-bar>
