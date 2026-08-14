<x-app-layout :heading="$user->name"
               :back="route('admin.users.show', $user)"
               :back-label="$user->name" width="narrow">
    <x-page-header :description="__('users.edit.description')" />


    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card :title="__('users.edit.title')">
            @include('admin.users.partials.fields', ['user' => $user])
        </x-card>

        <x-form.actions>
            <x-button :href="route('admin.users.show', $user)" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('users.edit.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
