<?php

declare(strict_types=1);

return [

    'client_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'client_notification_frequency' => [
        'one_time' => 'One time',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
    ],

    'client_notification_delivery_status' => [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'failed' => 'Failed',
    ],

    'client_email_template' => [
        'general_reminder' => 'General reminder',
        'monthly_reminder' => 'Monthly reminder',
        'annual_reminder' => 'Annual reminder',
    ],

    /*
    | Theme names are deliberately evocative rather than descriptive, the way editor
    | and terminal themes are named: they are remembered and asked for by name.
    */
    'theme' => [
        'iris' => [
            'label' => 'Iris',
            'description' => 'Minimal and modern, in soft violet',
        ],
        'cappuccino' => [
            'label' => 'Cappuccino',
            'description' => 'Classic and square-cut, warm paper and espresso',
        ],
        'bubblegum' => [
            'label' => 'Bubblegum',
            'description' => 'Loud, round and cheerful',
        ],
        'graphite' => [
            'label' => 'Graphite',
            'description' => 'Sober near-monochrome, ink on paper',
        ],
        'cathode' => [
            'label' => 'Cathode',
            'description' => 'A terminal: monospaced, square, phosphor green',
        ],
    ],

    'color_scheme' => [
        'system' => 'System',
        'light' => 'Light',
        'dark' => 'Dark',
    ],

    'locale' => [
        'en' => 'English',
        'pt' => 'Português',
    ],

    /*
    | Audit actions are dotted values such as "client.created". The translator reads a
    | dot as nesting, so these labels are nested to match the value exactly — a flat
    | 'client.created' key would never be found.
    */
    'audit_action' => [

        'client' => [
            'created' => 'Client created',
            'updated' => 'Client updated',
            'archived' => 'Client archived',
            'restored' => 'Client restored',
        ],

        'clients' => [
            'imported' => 'Clients imported',
        ],

        'client_notification_schedule' => [
            'created' => 'Schedule created',
            'updated' => 'Schedule updated',
            'enabled' => 'Schedule enabled',
            'disabled' => 'Schedule disabled',
            'deleted' => 'Schedule deleted',
        ],

        'user' => [
            'created' => 'User created',
            'updated' => 'User updated',
            'activated' => 'User activated',
            'deactivated' => 'User deactivated',
        ],

        'users' => [
            'imported' => 'Users imported',
        ],

        'role' => [
            'created' => 'Role created',
            'updated' => 'Role updated',
            'deleted' => 'Role deleted',
        ],

        'application_settings' => [
            'updated' => 'Application settings updated',
        ],

        'default_client_notifications' => [
            'updated' => 'Default notifications updated',
        ],

    ],

];
