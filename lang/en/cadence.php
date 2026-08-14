<?php

declare(strict_types=1);

return [

    'title' => 'Notification schedules',
    'description' => 'Every recurring client email this application knows about.',

    'upcoming' => [
        'title' => 'Upcoming notifications',
        'description' => 'What is going out next, nearest first.',
        'empty' => 'Nothing is scheduled',
        'empty_description' => 'Client emails appear here as soon as a schedule has an upcoming send date.',
        'filtered_empty' => 'Nothing matches these filters',
        'filtered_empty_description' => 'Try a wider time range or clear the filters.',
    ],

    'templates' => [
        'title' => 'Email templates',
        'description' => 'The email your clients receive, exactly as it is sent.',
        'source_notice' => 'Templates are part of the application, so their wording is reviewed like any other change and cannot be edited here. Adding one is a small development task.',
        'subject' => 'Subject',
        'preview' => 'Preview',
        'dialog_title' => 'Template preview',
        'sample_client' => 'Example Client',
        'sample_notice' => 'Previews use an example client and the current date.',
    ],

    'columns' => [
        'client' => 'Client',
        'frequency' => 'Frequency',
        'last_sent_at' => 'Last sent',
        'next_send_at' => 'Next send',
        'recipient' => 'Recipient',
        'starts_at' => 'Anchored on',
        'state' => 'State',
        'template' => 'Template',
    ],

    'fields' => [
        'frequency' => 'Frequency',
        'is_enabled' => 'Enabled',
        'starts_at' => 'First send date and time',
        'template' => 'Email template',
    ],

    'hints' => [
        'frequency' => 'A one-time notification is sent once and then closes itself.',
        'is_enabled' => 'A disabled schedule keeps its settings but sends nothing.',
        'starts_at' => 'Times are shown and entered in your timezone (:timezone).',
        'starts_at_anchor' => 'This date and time is the anchor of the recurrence. A monthly schedule anchored on the 31st falls back to the last day of shorter months.',
        'template' => 'Templates are part of the application and cannot be edited here.',
    ],

    'actions' => [
        'create' => 'New schedule',
        'delete' => 'Delete schedule',
        'disable' => 'Disable',
        'enable' => 'Enable',
    ],

    'states' => [
        'completed' => 'Completed',
        'disabled' => 'Disabled',
        'enabled' => 'Enabled',
        'no_next_occurrence' => 'No further occurrence',
    ],

    'state_hints' => [
        'completed' => 'This one-time notification has been sent.',
        'no_next_occurrence' => 'This schedule is enabled but has no upcoming date. Edit it to set a new one.',
    ],

    'filters' => [
        'frequency' => 'Frequency',
        'range' => 'Time range',
        'search' => 'Search client',
        'state' => 'State',
        'template' => 'Template',
    ],

    'ranges' => [
        'next_30_days' => 'Next 30 days',
        'next_7_days' => 'Next 7 days',
        'today' => 'Today',
    ],

    'create' => [
        'title' => 'New notification schedule',
        'description' => 'Choose one of the available email templates and say when :client should first receive it.',
        'submit' => 'Create schedule',
    ],

    'edit' => [
        'title' => 'Edit schedule',
        'description' => 'Changing the anchor or the frequency recalculates the next send date.',
        'submit' => 'Save schedule',
    ],

    'show' => [
        'title' => 'Schedule',
        'details' => 'Schedule',
        'deliveries' => 'Deliveries from this schedule',
        'deliveries_empty' => 'This schedule has not sent anything yet',
    ],

    'delete' => [
        'title' => 'Delete this schedule?',
        'message' => 'The schedule stops sending and disappears from the working lists. Everything it already sent stays in delivery history.',
        'confirm' => 'Delete schedule',
    ],

    'empty' => [
        'title' => 'No schedules yet',
        'description' => 'Open a client to create their first recurring email.',
        'filtered_title' => 'No schedules match these filters',
        'filtered_description' => 'Try a different search term or clear the filters.',
    ],

    'flash' => [
        'created' => 'The :template schedule has been created.',
        'deleted' => 'The schedule has been deleted.',
        'disabled' => 'The schedule has been disabled.',
        'enabled' => 'The schedule has been enabled.',
        'updated' => 'The schedule has been updated.',
    ],

];
