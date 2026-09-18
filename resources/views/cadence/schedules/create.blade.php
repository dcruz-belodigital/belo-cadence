@php
    /*
    | The same page serves both ways in: reached through a client it posts to that
    | client's route and cannot become anything else, reached from the schedules list it
    | asks what the notification is for.
    */
    $action = $client !== null
        ? route('clients.schedules.store', $client)
        : route('cadence.schedules.store');

    $cancel = $client !== null
        ? route('clients.show', $client)
        : route('cadence.schedules.index');
@endphp

<x-app-layout :heading="__('cadence.create.title')"
               :back="$cancel"
               :back-label="$client?->name ?? __('cadence.title')" width="narrow">
    <x-page-header :description="__('cadence.create.description')" />

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf

        <x-card :title="__('cadence.show.details')">
            @include('cadence.schedules.partials.fields')
        </x-card>

        <x-form.actions>
            <x-button :href="$cancel" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('cadence.create.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
