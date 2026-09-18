<x-app-layout :heading="$user->name"
               :back="route('admin.users.index')"
               :back-label="__('users.title')">
    <x-page-header :description="(string) $user->email">
        <x-slot:actions>
            @can('update', $user)
                <x-button :href="route('admin.users.edit', $user)" variant="secondary" icon="pencil">
                    {{ __('common.actions.edit') }}
                </x-button>
            @endcan

            @if ($user->is_active)
                @can('deactivate', $user)
                    <x-confirm-form :action="route('admin.users.deactivate', $user)"
                                    method="POST"
                                    :title="__('users.deactivate.title')"
                                    :message="__('users.deactivate.message')"
                                    :confirm="__('users.deactivate.confirm')"
                                    trigger-variant="secondary"
                                    trigger-size="md">
                        <x-slot:trigger>{{ __('users.actions.deactivate') }}</x-slot:trigger>
                    </x-confirm-form>
                @endcan
            @else
                @can('activate', $user)
                    <x-confirm-form :action="route('admin.users.activate', $user)"
                                    method="POST"
                                    :title="__('users.activate.title')"
                                    :message="__('users.activate.message')"
                                    :confirm="__('users.activate.confirm')"
                                    variant="primary"
                                    trigger-variant="primary"
                                    trigger-size="md"
                                    icon="check-circle">
                        <x-slot:trigger>{{ __('users.actions.activate') }}</x-slot:trigger>
                    </x-confirm-form>
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @unless ($user->is_active)
            <x-alert variant="warning">{{ __('users.show.inactive_notice') }}</x-alert>
        @endunless

        <x-card :title="__('users.show.details')">
            <x-detail-list>
                <x-detail-item :label="__('users.fields.name')">{{ $user->name }}</x-detail-item>
                <x-detail-item :label="__('users.fields.email')">{{ $user->email }}</x-detail-item>

                <x-detail-item :label="__('users.columns.state')">
                    <x-badge :variant="$user->is_active ? 'success' : 'neutral'">
                        {{ $user->is_active ? __('users.states.active') : __('users.states.inactive') }}
                    </x-badge>
                </x-detail-item>

                <x-detail-item :label="__('users.columns.created')">
                    <x-datetime :value="$user->created_at" />
                </x-detail-item>

                <x-detail-item :label="__('profile.fields.timezone')">{{ $user->timezone }}</x-detail-item>
                <x-detail-item :label="__('profile.fields.locale')">{{ $user->locale->label() }}</x-detail-item>
            </x-detail-list>
        </x-card>

        <x-card :title="__('users.show.roles')">
            @if ($user->roles->isEmpty())
                <p class="text-body text-foreground-muted">{{ __('users.show.no_roles') }}</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($user->roles as $role)
                        @can('view', $role)
                            <a href="{{ route('admin.roles.show', $role) }}" class="focus-ring rounded-pill">
                                <x-badge variant="info">{{ $role->name }}</x-badge>
                            </a>
                        @else
                            <x-badge variant="info">{{ $role->name }}</x-badge>
                        @endcan
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
