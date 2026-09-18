<?php

declare(strict_types=1);

return [

    'title' => 'Delivery history',
    'description' => 'Every email this application attempted to send, exactly as it was produced.',

    'columns' => [
        'attempted_at' => 'Attempted',
        'client' => 'Client',
        'failure' => 'Failure',
        'recipient' => 'Recipient',
        'scheduled_for' => 'Scheduled for',
        'sender' => 'Sender',
        'sent_at' => 'Sent',
        'sent_by' => 'Sent by',
        'target' => 'Sent to',
        'source' => 'Source',
        'status' => 'Status',
        'subject' => 'Subject',
        'template' => 'Template',
    ],

    'actions' => [
        'send' => 'Send now',
    ],

    'fields' => [
        'client' => 'Client',
        'template' => 'Email template',
    ],

    'hints' => [
        'client' => 'Only active clients can be emailed. An archived or inactive client is not listed.',
        'template' => 'Templates are part of the application and cannot be edited here.',
    ],

    'send' => [
        'title' => 'Send a notification now',
        'description' => 'Send one of the email templates straight away — to a client, or to addresses you type here — outside any schedule. It goes out as soon as you confirm, and is recorded in delivery history as a manual send.',
        'submit' => 'Send now',
        'choose_client' => 'Choose a client',
        'no_clients' => 'There is no client to email',
        'no_clients_description' => 'Only active clients can receive email. Add one, or reactivate a client, and then send.',
    ],

    'filters' => [
        'client' => 'Client',
        'from' => 'From date',
        'search' => 'Search subject or recipient',
        'source' => 'Source',
        'status' => 'Status',
        'template' => 'Template',
        'to' => 'To date',
    ],

    'show' => [
        'title' => 'Delivery',
        'details' => 'Delivery',
        'snapshot' => 'Message as it was sent',
        'snapshot_notice' => 'This is the exact message that was produced for this occurrence. It never changes, even when the client, the sender or the template does.',
        'failure' => 'Failure details',
        'schedule' => 'Schedule',
        'schedule_deleted' => 'The schedule that produced this delivery has since been deleted.',
        'no_schedule' => 'Sent by hand, so no schedule produced it.',
        'manual_notice' => 'This email was sent by hand rather than by a schedule. Nothing about the client\'s schedules changed.',
        'sent_by_system' => 'The scheduler',
        'no_body' => 'No message content was produced for this attempt.',
    ],

    'flash' => [
        'sent' => ':count email(s) to :target sent.',
        'failed' => ':failed of :total emails to :target could not be sent. Every attempt and its reason is recorded in history.',
    ],

    'empty' => [
        'title' => 'Nothing has been sent yet',
        'description' => 'Deliveries appear here as soon as the scheduler processes a due notification.',
        'filtered_title' => 'No deliveries match these filters',
        'filtered_description' => 'Try a different date range or clear the filters.',
    ],

];
