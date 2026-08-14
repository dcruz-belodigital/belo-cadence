@php
    $grantedPermissions = $role->permissions->pluck('name')->all();
@endphp

<x-app-layout :heading="$role->name"
               :back="route('admin.roles.index')"
               :back-label="__('roles.title')">
    <x-page-header>
        <x-slot:actions>
            @can('update', $role)
                <x-button :href="route('admin.roles.edit', $role)" variant="secondary" icon="pencil">
                    {{ __('common.actions.edit') }}
                </x-button>
            @endcan

            @can('delete', $role)
                <x-confirm-form :action="route('admin.roles.destroy', $role)"
                                method="DELETE"
                                :title="__('roles.delete.title')"
                                :message="__('roles.delete.message')"
                                :confirm="__('roles.delete.confirm')"
                                trigger-variant="secondary"
                                trigger-size="md"
                                icon="trash">
                    <x-slot:trigger>{{ __('roles.actions.delete') }}</x-slot:trigger>
                </x-confirm-form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        @if ($role->users->isNotEmpty())
            <x-alert variant="info">
                {{ __('roles.show.in_use_notice', ['count' => $role->users->count()]) }}
            </x-alert>
        @endif

        <x-card :title="__('roles.show.permissions')">
            @if ($grantedPermissions === [])
                <p class="text-body text-foreground-muted">{{ __('roles.show.no_permissions') }}</p>
            @else
                <div class="space-y-4">
                    @foreach ($groupedPermissions as $group => $permissions)
                        @php
                            $granted = collect($permissions)->filter(fn ($permission) => in_array($permission->value, $grantedPermissions, true));
                        @endphp

                        @if ($granted->isNotEmpty())
                            <div class="space-y-2">
                                <p class="text-overline uppercase text-foreground-muted">{{ $permissions[0]->groupLabel() }}</p>

                                <div class="flex flex-wrap gap-2">
                                    @foreach ($granted as $permission)
                                        <x-badge variant="neutral">{{ $permission->label() }}</x-badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card :title="__('roles.show.users')">
            @if ($role->users->isEmpty())
                <p class="text-body text-foreground-muted">{{ __('roles.show.no_users') }}</p>
            @else
                <ul class="divide-y divide-border">
                    @foreach ($role->users as $user)
                        <li class="flex items-center justify-between gap-3 py-2 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="truncate text-label">{{ $user->name }}</p>
                                <p class="truncate text-meta text-foreground-muted">{{ $user->email }}</p>
                            </div>

                            @can('view', $user)
                                <x-button :href="route('admin.users.show', $user)" variant="ghost" size="sm" icon="eye">
                                    {{ __('common.actions.view') }}
                                </x-button>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-app-layout>
