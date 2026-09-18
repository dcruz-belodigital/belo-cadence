<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * State-changing operations worth recording in the audit log.
 *
 * Sending is not represented here: delivery history already records
 * every attempt in far more detail.
 */
enum AuditAction: string
{
    case ClientCreated = 'client.created';
    case ClientUpdated = 'client.updated';
    case ClientArchived = 'client.archived';
    case ClientRestored = 'client.restored';
    case ClientsImported = 'clients.imported';

    case NotificationScheduleCreated = 'notification_schedule.created';
    case NotificationScheduleUpdated = 'notification_schedule.updated';
    case NotificationScheduleEnabled = 'notification_schedule.enabled';
    case NotificationScheduleDisabled = 'notification_schedule.disabled';
    case NotificationScheduleDeleted = 'notification_schedule.deleted';

    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserActivated = 'user.activated';
    case UserDeactivated = 'user.deactivated';
    case UsersImported = 'users.imported';

    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';

    case ApplicationSettingsUpdated = 'application_settings.updated';
    case DefaultClientNotificationsUpdated = 'default_client_notifications.updated';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $action): string => $action->value, self::cases());
    }

    public function label(): string
    {
        return __('enums.audit_action.'.$this->value);
    }

    public function badgeVariant(): BadgeVariant
    {
        return match (Str::afterLast($this->value, '.')) {
            'created', 'imported' => BadgeVariant::Success,
            'deleted', 'archived', 'deactivated' => BadgeVariant::Danger,
            'enabled', 'activated', 'restored' => BadgeVariant::Info,
            default => BadgeVariant::Neutral,
        };
    }
}
