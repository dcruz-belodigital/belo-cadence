<?php

declare(strict_types=1);

/*
| Permission names are dotted values such as "clients.viewAny". The translator reads a
| dot as nesting, so the labels below are nested to match the value exactly — a flat
| 'clients.viewAny' key would never be found.
*/

return [

    'groups' => [
        'application-settings' => 'Application settings',
        'audit-log' => 'Audit log',
        'client-notifications' => 'Notification schedules',
        'clients' => 'Clients',
        'dashboard' => 'Dashboard',
        'default-client-notifications' => 'Default notifications',
        'notification-deliveries' => 'Delivery history',
        'roles' => 'Roles',
        'users' => 'Users',
    ],

    'names' => [

        'dashboard' => [
            'view' => 'View dashboard',
        ],

        'clients' => [
            'viewAny' => 'List clients',
            'view' => 'View a client',
            'create' => 'Create clients',
            'update' => 'Update clients',
            'delete' => 'Archive and restore clients',
            'import' => 'Import clients from CSV',
            'export' => 'Export clients to CSV',
        ],

        'client-notifications' => [
            'viewAny' => 'List notification schedules',
            'view' => 'View a notification schedule',
            'create' => 'Create notification schedules',
            'update' => 'Update, enable and disable schedules',
            'delete' => 'Delete notification schedules',
            'export' => 'Export notification schedules to CSV',
        ],

        'notification-deliveries' => [
            'viewAny' => 'List delivery history',
            'view' => 'View a delivery',
            'export' => 'Export delivery history to CSV',
        ],

        'users' => [
            'viewAny' => 'List users',
            'view' => 'View a user',
            'create' => 'Create users',
            'update' => 'Update users, roles and activation',
            'import' => 'Import users from CSV',
            'export' => 'Export users to CSV',
        ],

        'roles' => [
            'viewAny' => 'List roles',
            'view' => 'View a role',
            'create' => 'Create roles',
            'update' => 'Update roles and their permissions',
            'delete' => 'Delete roles',
            'export' => 'Export roles to CSV',
        ],

        'audit-log' => [
            'viewAny' => 'List audit entries',
            'view' => 'View an audit entry',
            'export' => 'Export the audit log to CSV',
        ],

        'application-settings' => [
            'view' => 'View application settings',
            'update' => 'Update application settings',
        ],

        'default-client-notifications' => [
            'view' => 'View default notifications',
            'update' => 'Update default notifications',
        ],

    ],

];
