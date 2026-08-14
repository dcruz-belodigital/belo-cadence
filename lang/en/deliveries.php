<?php

declare(strict_types=1);

return [

    'title' => 'Delivery history',
    'description' => 'Every client email this application attempted to send, exactly as it was produced.',

    'columns' => [
        'attempted_at' => 'Attempted',
        'client' => 'Client',
        'failure' => 'Failure',
        'recipient' => 'Recipient',
        'scheduled_for' => 'Scheduled for',
        'sender' => 'Sender',
        'sent_at' => 'Sent',
        'status' => 'Status',
        'subject' => 'Subject',
        'template' => 'Template',
    ],

    'filters' => [
        'client' => 'Client',
        'from' => 'From date',
        'search' => 'Search subject or recipient',
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
        'no_body' => 'No message content was produced for this attempt.',
    ],

    'empty' => [
        'title' => 'Nothing has been sent yet',
        'description' => 'Deliveries appear here as soon as the scheduler processes a due notification.',
        'filtered_title' => 'No deliveries match these filters',
        'filtered_description' => 'Try a different date range or clear the filters.',
    ],

];
