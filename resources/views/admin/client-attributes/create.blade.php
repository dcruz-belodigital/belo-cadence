<x-app-layout :heading="__('client_attributes.create.title')"
               :back="route('admin.client-attributes.index')"
               :back-label="__('client_attributes.title')" width="narrow">
    <x-page-header :description="__('client_attributes.create.description')" />

    <form method="POST" action="{{ route('admin.client-attributes.store') }}" class="space-y-6">
        @csrf

        <x-card>
            @include('admin.client-attributes.partials.fields')
        </x-card>

        <x-form.actions>
            <x-button :href="route('admin.client-attributes.index')" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('client_attributes.create.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
