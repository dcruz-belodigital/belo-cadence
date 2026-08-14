<x-app-layout :heading="$client->name"
               :back="route('clients.show', $client)"
               :back-label="$client->name" width="narrow">
    <x-page-header :description="__('clients.edit.description')" />


    <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card :title="__('clients.edit.title')">
            @include('clients.partials.fields', ['client' => $client])
        </x-card>

        <x-form.actions>
            <x-button :href="route('clients.show', $client)" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('clients.edit.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
