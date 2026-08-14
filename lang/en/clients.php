<?php

declare(strict_types=1);

return [

    'title' => 'Clients',
    'description' => 'The organisations Belo Cadence sends recurring email to.',

    'columns' => [
        'created' => 'Created',
        'email' => 'Email',
        'name' => 'Name',
        'schedules' => 'Schedules',
        'status' => 'Status',
    ],

    'fields' => [
        'email' => 'Email address',
        'name' => 'Client name',
        'notes' => 'Internal notes',
        'status' => 'Status',
    ],

    'hints' => [
        'email' => 'Scheduled client emails are sent to this address.',
        'notes' => 'Only visible inside Belo Cadence. Never sent to the client.',
    ],

    'actions' => [
        'archive' => 'Archive',
        'create' => 'New client',
        'restore' => 'Restore',
    ],

    'filters' => [
        'archived' => 'Include archived',
        'search' => 'Search name or email',
        'status' => 'Status',
    ],

    'create' => [
        'title' => 'New client',
        'description' => 'Add a client and, if you want, start its recurring email straight away.',
        'details' => 'Client details',
        'defaults' => 'Default notifications',
        'defaults_description' => 'These are the notification schedules your team applies to new clients. Tick the ones this client should receive and set the first send date for each.',
        'defaults_empty' => 'No default notifications are configured, so this client starts without any schedules. You can add them from the client page afterwards.',
        'apply' => 'Apply to this client',
        'enabled' => 'Start sending immediately',
        'submit' => 'Create client',
    ],

    'edit' => [
        'title' => 'Edit client',
        'description' => 'Changing these details never alters emails that were already sent.',
        'submit' => 'Save client',
    ],

    'show' => [
        'archived_notice' => 'This client is archived. Its schedules are switched off and no email is sent on its behalf.',
        'inactive_notice' => 'This client is inactive, so no scheduled email is sent to it.',
        'details' => 'Client details',
        'schedules' => 'Notification schedules',
        'schedules_description' => 'What this client receives, and when.',
        'schedules_empty' => 'No schedules yet',
        'schedules_empty_description' => 'Create a schedule to start sending recurring email to this client.',
        'deliveries' => 'Recent deliveries',
        'deliveries_description' => 'The most recent send attempts for this client.',
        'deliveries_empty' => 'Nothing has been sent to this client yet',
        'notes' => 'Notes',
    ],

    'empty' => [
        'title' => 'No clients yet',
        'description' => 'Add your first client to start scheduling recurring email.',
        'filtered_title' => 'No clients match these filters',
        'filtered_description' => 'Try a different search term or clear the filters.',
    ],

    'archive' => [
        'title' => 'Archive this client?',
        'message' => 'The client is hidden from the working list and its schedules are switched off. Delivery history is kept exactly as it is, and the client can be restored later.',
        'confirm' => 'Archive client',
    ],

    'restore' => [
        'title' => 'Restore this client?',
        'message' => 'The client returns to the working list. Its schedules stay switched off until you enable them again.',
        'confirm' => 'Restore client',
    ],

    'flash' => [
        'archived' => ':name has been archived.',
        'created' => ':name has been created.',
        'restored' => ':name has been restored.',
        'updated' => ':name has been updated.',
    ],

];
