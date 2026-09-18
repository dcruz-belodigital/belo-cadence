<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Every permission the application understands.
 *
 * Permissions are source-controlled: they are seeded from this enum and may never
 * be created from the UI. Roles decide which of them a user receives.
 */
enum PermissionName: string
{
    case DashboardView = 'dashboard.view';

    case ClientsViewAny = 'clients.viewAny';
    case ClientsView = 'clients.view';
    case ClientsCreate = 'clients.create';
    case ClientsUpdate = 'clients.update';
    case ClientsDelete = 'clients.delete';
    case ClientsImport = 'clients.import';
    case ClientsExport = 'clients.export';

    case NotificationsViewAny = 'notifications.viewAny';
    case NotificationsView = 'notifications.view';
    case NotificationsCreate = 'notifications.create';
    case NotificationsUpdate = 'notifications.update';
    case NotificationsDelete = 'notifications.delete';
    case NotificationsSend = 'notifications.send';
    case NotificationsExport = 'notifications.export';

    case NotificationDeliveriesViewAny = 'notification-deliveries.viewAny';
    case NotificationDeliveriesView = 'notification-deliveries.view';
    case NotificationDeliveriesExport = 'notification-deliveries.export';

    case UsersViewAny = 'users.viewAny';
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersImport = 'users.import';
    case UsersExport = 'users.export';

    case RolesViewAny = 'roles.viewAny';
    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';
    case RolesExport = 'roles.export';

    case AuditLogViewAny = 'audit-log.viewAny';
    case AuditLogView = 'audit-log.view';
    case AuditLogExport = 'audit-log.export';

    case ApplicationSettingsView = 'application-settings.view';
    case ApplicationSettingsUpdate = 'application-settings.update';

    case DefaultClientNotificationsView = 'default-client-notifications.view';
    case DefaultClientNotificationsUpdate = 'default-client-notifications.update';

    /**
     * Permissions without which nobody could administer access again.
     *
     * @return list<self>
     */
    public static function essentialAdministrationPermissions(): array
    {
        return [
            self::RolesViewAny,
            self::RolesUpdate,
            self::UsersViewAny,
            self::UsersUpdate,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $permission): string => $permission->value, self::cases());
    }

    /**
     * Every permission that only reads.
     *
     * Derived from the naming convention rather than listed by hand, so a permission
     * added for a new resource joins the read-only set on its own. Exporting is left
     * out deliberately: it takes data out of the application rather than showing it.
     *
     * @return list<string>
     */
    public static function readOnlyValues(): array
    {
        return array_values(array_map(
            static fn (self $permission): string => $permission->value,
            array_filter(
                self::cases(),
                static fn (self $permission): bool => Str::endsWith($permission->value, ['.view', '.viewAny']),
            ),
        ));
    }

    /**
     * Permissions grouped by resource, in declaration order, for the role form.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::cases() as $permission) {
            $grouped[$permission->group()][] = $permission;
        }

        return $grouped;
    }

    public function group(): string
    {
        return Str::before($this->value, '.');
    }

    public function label(): string
    {
        return __('permissions.names.'.$this->value);
    }

    public function groupLabel(): string
    {
        return __('permissions.groups.'.$this->group());
    }
}
