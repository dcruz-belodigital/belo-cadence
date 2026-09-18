<?php

declare(strict_types=1);

return [

    'title' => 'Audit log',
    'description' => 'Who changed what. Read-only by design.',

    'columns' => [
        'action' => 'Action',
        'actor' => 'Actor',
        'record' => 'Record',
        'when' => 'When',
    ],

    'filters' => [
        'action' => 'Action',
        'from' => 'From date',
        'search' => 'Search actor',
        'to' => 'To date',
        'type' => 'Record type',
    ],

    'system_actor' => 'System',
    'system_actor_hint' => 'Performed by the application itself, not by a person.',

    'show' => [
        'title' => 'Audit entry',
        'details' => 'Entry',
        'old_values' => 'Before',
        'new_values' => 'After',
        'metadata' => 'Additional information',
        'no_values' => 'No values were recorded for this entry.',
        'record_missing' => 'The record this entry refers to no longer exists.',
    ],

    'empty' => [
        'title' => 'No audit entries yet',
        'description' => 'Entries appear as soon as somebody changes something worth recording.',
        'filtered_title' => 'No entries match these filters',
        'filtered_description' => 'Try a different date range or clear the filters.',
    ],

    'auditable_types' => [
        'application_settings' => 'Application settings',
        'audit' => 'Audit entry',
        'client' => 'Client',
        'notification_delivery' => 'Delivery',
        'notification_schedule' => 'Notification schedule',
        'default_client_notification' => 'Default notification',
        'role' => 'Role',
        'user' => 'User',
    ],

];
