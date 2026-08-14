<?php

declare(strict_types=1);

return [

    'title' => 'Application settings',
    'description' => 'How Belo Cadence presents itself and who client email comes from.',

    'fields' => [
        'application_name' => 'Application name',
        'client_email_sender_email' => 'Client email sender address',
        'client_email_sender_name' => 'Client email sender name',
        'default_locale' => 'Default language',
        'default_theme' => 'Default theme',
        'default_timezone' => 'Default timezone',
    ],

    'hints' => [
        'application_name' => 'Shown in the interface and used in client email copy.',
        'client_email_sender' => 'Used for scheduled client email from now on. Deliveries already recorded keep the sender they were sent with.',
        'default_locale' => 'Used for client email and for people who have not chosen a language.',
        'default_theme' => 'How the application looks for everyone who has not chosen a theme of their own.',
        'default_timezone' => 'Recurring send dates are calculated in this timezone, and it is the fallback for people who have not chosen one.',
        'infrastructure' => 'Mail server credentials are deliberately not editable here: they belong in the environment configuration.',
    ],

    'sections' => [
        'application' => 'Application',
        'client_email' => 'Client email',
    ],

    'color_scheme' => [
        'label' => 'Colour scheme',
    ],

    'submit' => 'Save settings',

    'flash' => [
        'updated' => 'Application settings have been updated.',
    ],

    'defaults' => [
        'title' => 'Default notifications',
        'description' => 'The notification schedules offered when a new client is created.',
        'explanation' => 'Applying one of these to a client copies it. Changing this list later never alters schedules that already exist.',
        'columns' => [
            'template' => 'Template',
            'frequency' => 'Frequency',
            'enabled' => 'Enabled by default',
        ],
        'add' => 'Add default',
        'remove' => 'Remove',
        'empty' => 'No defaults configured',
        'empty_description' => 'New clients will start without any schedules until you add one here.',
        'submit' => 'Save defaults',
        'flash' => [
            'updated' => 'Default notifications have been updated.',
        ],
    ],

];
