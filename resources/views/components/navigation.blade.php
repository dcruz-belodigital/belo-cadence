@props(['applicationName'])

@php
    use App\Enums\PermissionName;
@endphp

<nav class="flex min-h-0 flex-1 flex-col gap-7 overflow-y-auto px-3 pb-4 pt-4 lg:pt-5" aria-label="{{ __('navigation.aria_label') }}">
    {{--
        The mark sits on a solid primary tile whose corner radius is the theme's own, so
        the logo is square-cut or round depending on which theme is being worn.
    --}}
    <a href="{{ route('dashboard') }}" class="focus-ring mx-1 flex items-center gap-2.5 rounded-control">
        <span class="flex size-8 items-center justify-center rounded-control bg-primary text-primary-foreground">
            <x-logo size="size-5" />
        </span>
        <span class="truncate text-section-title">{{ $applicationName }}</span>
    </a>

    <div class="space-y-6">
        <ul class="space-y-0.5">
            @can(PermissionName::DashboardView->value)
                <li>
                    <x-nav-item :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">
                        {{ __('navigation.dashboard') }}
                    </x-nav-item>
                </li>
            @endcan

            @can(PermissionName::ClientsViewAny->value)
                <li>
                    <x-nav-item :href="route('clients.index')" icon="building" :active="request()->routeIs('clients.*')">
                        {{ __('navigation.clients') }}
                    </x-nav-item>
                </li>
            @endcan
        </ul>

        @canany([PermissionName::NotificationsViewAny->value, PermissionName::NotificationDeliveriesViewAny->value])
            <x-nav-section :title="__('navigation.sections.cadence')">
                @can(PermissionName::NotificationsViewAny->value)
                    <li>
                        <x-nav-item :href="route('cadence.upcoming')" icon="calendar" :active="request()->routeIs('cadence.upcoming')">
                            {{ __('navigation.upcoming') }}
                        </x-nav-item>
                    </li>
                    <li>
                        <x-nav-item :href="route('cadence.schedules.index')" icon="clock" :active="request()->routeIs('cadence.schedules.*')">
                            {{ __('navigation.schedules') }}
                        </x-nav-item>
                    </li>
                    <li>
                        <x-nav-item :href="route('cadence.email-templates')" icon="document" :active="request()->routeIs('cadence.email-templates')">
                            {{ __('navigation.email_templates') }}
                        </x-nav-item>
                    </li>
                @endcan

                @can(PermissionName::NotificationDeliveriesViewAny->value)
                    <li>
                        <x-nav-item :href="route('cadence.deliveries.index')" icon="envelope" :active="request()->routeIs('cadence.deliveries.*')">
                            {{ __('navigation.deliveries') }}
                        </x-nav-item>
                    </li>
                @endcan
            </x-nav-section>
        @endcanany

        @canany([
            PermissionName::UsersViewAny->value,
            PermissionName::RolesViewAny->value,
            PermissionName::AuditLogViewAny->value,
            PermissionName::ClientAttributesViewAny->value,
            PermissionName::ApplicationSettingsView->value,
            PermissionName::DefaultClientNotificationsView->value,
        ])
            <x-nav-section :title="__('navigation.sections.administration')">
                @can(PermissionName::UsersViewAny->value)
                    <li>
                        <x-nav-item :href="route('admin.users.index')" icon="users" :active="request()->routeIs('admin.users.*')">
                            {{ __('navigation.users') }}
                        </x-nav-item>
                    </li>
                @endcan

                @can(PermissionName::RolesViewAny->value)
                    <li>
                        <x-nav-item :href="route('admin.roles.index')" icon="shield" :active="request()->routeIs('admin.roles.*')">
                            {{ __('navigation.roles') }}
                        </x-nav-item>
                    </li>
                @endcan

                @can(PermissionName::ClientAttributesViewAny->value)
                    <li>
                        <x-nav-item :href="route('admin.client-attributes.index')" icon="key" :active="request()->routeIs('admin.client-attributes.*')">
                            {{ __('navigation.client_attributes') }}
                        </x-nav-item>
                    </li>
                @endcan

                @can(PermissionName::AuditLogViewAny->value)
                    <li>
                        <x-nav-item :href="route('admin.audit-log.index')" icon="clipboard" :active="request()->routeIs('admin.audit-log.*')">
                            {{ __('navigation.audit_log') }}
                        </x-nav-item>
                    </li>
                @endcan

                {{-- The default notifications are a section of this page, so either permission reaches it. --}}
                @canany([PermissionName::ApplicationSettingsView->value, PermissionName::DefaultClientNotificationsView->value])
                    <li>
                        <x-nav-item :href="route('admin.settings.edit')" icon="cog" :active="request()->routeIs('admin.settings.*')">
                            {{ __('navigation.settings') }}
                        </x-nav-item>
                    </li>
                @endcanany
            </x-nav-section>
        @endcanany

        {{-- Open to everybody who can sign in. --}}
        <x-nav-section :title="__('navigation.sections.support')">
            <li>
                <x-nav-item :href="route('support.user-guide')" icon="book" :active="request()->routeIs('support.user-guide')">
                    {{ __('navigation.user_guide') }}
                </x-nav-item>
            </li>
            <li>
                <x-nav-item :href="route('support.changelog')" icon="scroll" :active="request()->routeIs('support.changelog')">
                    {{ __('navigation.changelog') }}
                </x-nav-item>
            </li>
        </x-nav-section>
    </div>
</nav>
