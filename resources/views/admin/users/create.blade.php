<x-app-layout :heading="__('users.create.title')" :back="route('admin.users.index')" :back-label="__('users.title')" width="narrow">
    <x-page-header :description="__('users.create.description')" />

    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
        @csrf

        <x-card :title="__('users.show.details')">
            @include('admin.users.partials.fields')
        </x-card>

        <x-form.actions>
            <x-button :href="route('admin.users.index')" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('users.create.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
