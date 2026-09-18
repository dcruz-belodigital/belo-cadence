<x-app-layout :heading="$attribute->name"
               :back="route('admin.client-attributes.show', $attribute)"
               :back-label="$attribute->name" width="narrow">
    <x-page-header :description="__('client_attributes.edit.description')" />

    <form method="POST" action="{{ route('admin.client-attributes.update', $attribute) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card :title="__('client_attributes.edit.title')">
            @include('admin.client-attributes.partials.fields', ['attribute' => $attribute])
        </x-card>

        <x-form.actions>
            <x-button :href="route('admin.client-attributes.show', $attribute)" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('client_attributes.edit.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
