<?php

declare(strict_types=1);

return [

    'title' => 'Notifications',
    'description' => 'What the application wants you to know about.',

    'empty' => 'Nothing to report',
    'empty_description' => 'Notifications about failed client email appear here.',
    'fallback_title' => 'Notification',
    'mark_all_read' => 'Mark all as read',
    'unread' => 'Unread',
    'unread_count' => ':count unread notification(s)',
    'view_all' => 'All notifications',

    'columns' => [
        'received' => 'Received',
    ],

    'flash' => [
        'all_read' => 'All notifications have been marked as read.',
    ],

    'delivery_failed' => [
        'title' => 'A client email failed to send',
        'message' => 'The :template for :client could not be delivered.',
    ],

];
