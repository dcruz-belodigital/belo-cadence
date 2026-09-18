<?php

declare(strict_types=1);

return [

    'title' => 'Notification schedules',
    'description' => 'Every recurring email this application sends, to clients and to recipient lists alike.',

    'upcoming' => [
        'title' => 'Upcoming notifications',
        'description' => 'What is going out next, nearest first.',
        'empty' => 'Nothing is scheduled',
        'empty_description' => 'Notifications appear here as soon as a schedule has an upcoming send date.',
        'filtered_empty' => 'Nothing matches these filters',
        'filtered_empty_description' => 'Try a wider time range or clear the filters.',
    ],

    'templates' => [
        'audience' => 'Audience',
        'sample_list' => 'Example List',
        'sample_subject' => 'Your subject line goes here',
        'sample_message' => "This is where the message you write on the schedule appears.\n\nBlank lines become new paragraphs.",
        'title' => 'Email templates',
        'description' => 'Every template this application can send, exactly as it arrives. Each one is written for a client or for a recipient list.',
        'source_notice' => 'Templates are part of the application, so their wording is reviewed like any other change and cannot be edited here. Adding one is a small development task.',
        'subject' => 'Subject',
        'preview' => 'Preview',
        'dialog_title' => 'Template preview',
        'sample_client' => 'Example Client',
        'sample_notice' => 'Previews use an example name and the current date.',
    ],

    'recipient_count' => '{0} No recipients|{1} 1 recipient|[2,*] :count recipients',

    'columns' => [
        'name' => 'Notification',
        'recipients' => 'Recipients',
        'target' => 'Sends to',
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
        'client' => 'Client',
        'message' => 'Message',
        'name' => 'Notification name',
        'recipients' => 'Recipient emails',
        'subject' => 'Subject',
        'target' => 'Sends to',
        'frequency' => 'Frequency',
        'is_enabled' => 'Enabled',
        'starts_at' => 'First send date and time',
        'template' => 'Email template',
    ],

    'slots' => [
        'title' => 'Template values',
        'description' => 'What this template fills in from the client. Choose one of the client\'s attributes, or type a value used by this notification alone.',
        'manual' => 'Type a value',
        'value' => 'Value',
    ],

    'errors' => [
        'unresolved_slot' => 'The template value :slot could not be filled in for this client.',
    ],

    'hints' => [
        'client' => 'Only active clients can be emailed. An archived or inactive client is not listed.',
        'message' => 'Plain text. Leave a blank line between paragraphs. It is sent exactly as typed.',
        'name' => 'What this notification is called in the lists, and the name the templates print.',
        'recipients' => 'One email address per line. Everyone on the list gets their own copy.',
        'subject' => 'The subject line of the email.',
        'target' => 'A client schedule sends to that client and is tracked under them. A recipient list sends to the addresses you give it.',
        'frequency' => 'A one-time notification is sent once and then closes itself.',
        'is_enabled' => 'A disabled schedule keeps its settings but sends nothing.',
        'starts_at_anchor' => 'This date and time is the anchor of the recurrence. A monthly schedule anchored on the 31st falls back to the last day of shorter months.',
        'template' => 'Templates are part of the application and cannot be edited here.',
    ],

    'actions' => [
        'create_list' => 'New notification',
        'send' => 'Send now',
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
        'target' => 'Sends to',
        'frequency' => 'Frequency',
        'range' => 'Time range',
        'search' => 'Search name or client',
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
        'description' => 'Choose what goes out, who receives it, and when it first sends.',
        'submit' => 'Create schedule',
    ],

    'edit' => [
        'title' => 'Edit schedule',
        'description' => 'Changing the anchor or the frequency recalculates the next send date.',
        'submit' => 'Save schedule',
    ],

    'show' => [
        'recipients' => 'Recipients',
        'message' => 'Message',
        'subject' => 'Subject',
        'title' => 'Schedule',
        'details' => 'Schedule',
        'deliveries' => 'Deliveries from this schedule',
        'deliveries_empty' => 'This schedule has not sent anything yet',
    ],

    'send' => [
        'title' => 'Send this notification now?',
        'message' => 'The email goes out immediately, to everyone this notification is addressed to. The schedule itself is untouched: nothing is rescheduled and no occurrence is used up.',
        'confirm' => 'Send now',
    ],

    'enable' => [
        'title' => 'Enable this schedule?',
        'message' => 'It starts sending again from its next occurrence. Nothing goes out right now.',
        'confirm' => 'Enable',
    ],

    'disable' => [
        'title' => 'Disable this schedule?',
        'message' => 'Nothing more goes out until it is enabled again. The schedule keeps its wording, its dates and everything it has already sent.',
        'confirm' => 'Disable',
    ],

    'delete' => [
        'title' => 'Delete this schedule?',
        'message' => 'The schedule stops sending and disappears from the working lists. Everything it already sent stays in delivery history.',
        'confirm' => 'Delete schedule',
    ],

    'empty' => [
        'title' => 'No schedules yet',
        'description' => 'Create one for a client, or for a list of addresses of your own.',
        'filtered_title' => 'No schedules match these filters',
        'filtered_description' => 'Try a different search term or clear the filters.',
    ],

    'flash' => [
        'send_no_recipients' => 'This notification has no recipients, so nothing was sent.',
        'sent' => ':count email(s) sent.',
        'sent_with_failures' => ':failed of :total emails could not be sent. Delivery history has the reason for each.',
        'created' => 'The :template schedule has been created.',
        'deleted' => 'The schedule has been deleted.',
        'disabled' => 'The schedule has been disabled.',
        'enabled' => 'The schedule has been enabled.',
        'updated' => 'The schedule has been updated.',
    ],

];
