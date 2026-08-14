<x-app-layout :heading="__('roles.create.title')" :back="route('admin.roles.index')" :back-label="__('roles.title')" width="narrow">
    <x-page-header :description="__('roles.create.description')" />

    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
        @csrf

        <x-card>
            @include('admin.roles.partials.fields')
        </x-card>

        <x-form.actions>
            <x-button :href="route('admin.roles.index')" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('roles.create.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
