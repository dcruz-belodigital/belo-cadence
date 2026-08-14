<x-app-layout :heading="$role->name"
               :back="route('admin.roles.show', $role)"
               :back-label="$role->name" width="narrow">


    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card>
            @include('admin.roles.partials.fields', ['role' => $role])
        </x-card>

        <x-form.actions>
            <x-button :href="route('admin.roles.show', $role)" variant="secondary" type="button">
                {{ __('common.actions.cancel') }}
            </x-button>

            <x-button type="submit">{{ __('roles.edit.submit') }}</x-button>
        </x-form.actions>
    </form>
</x-app-layout>
