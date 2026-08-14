<x-app-layout :heading="__('cadence.create.title')"
               :back="route('clients.show', $client)"
               :back-label="$client->name" width="narrow">
    <x-page-header :description="__('cadence.create.description', ['client' => $client->name])" />


    <form method="POST" action="{{ route('clients.schedules.store', $client) }}" class="space-y-6">
        @csrf

        <x-card :title="__('cadence.show.details')">
            @include('cadence.schedules.partials.fields')
        </x-card>

        <x-form.actions>
            <x-button :href="route('clients.show', $client)" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('cadence.create.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
