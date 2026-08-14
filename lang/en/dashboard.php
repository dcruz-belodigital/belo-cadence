<?php

declare(strict_types=1);

return [

    'title' => 'Dashboard',
    'description' => 'What needs attention today.',

    'metrics' => [
        'due_today' => 'Due today',
        'due_today_hint' => 'Including anything overdue',
        'due_this_week' => 'Due in the next 7 days',
        'sent_this_month' => 'Sent this month',
        'failed_this_month' => 'Failed this month',
    ],

    'panels' => [
        'upcoming' => 'Next notifications',
        'upcoming_all' => 'View all upcoming',
        'upcoming_empty' => 'Nothing is scheduled',
        'failures' => 'Recent failures',
        'failures_all' => 'View delivery history',
        'failures_empty' => 'No failed deliveries',
        'failures_empty_description' => 'Every attempt so far has been delivered.',
        'recent' => 'Recent activity',
        'recent_empty' => 'Nothing has been sent yet',
    ],

];
