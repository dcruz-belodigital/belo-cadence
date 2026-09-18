<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Email Copy
    |--------------------------------------------------------------------------
    |
    | The wording of the source-controlled email templates. Adding a
    | template means adding a section here as well.
    |
    */

    'notifications' => [

        'greeting' => 'Hello :name,',
        'closing' => 'Kind regards,',
        'footer' => 'You are receiving this message because :application sends it as part of an agreed schedule.',
        'scheduled_for' => 'Scheduled for :date',

        'greeting_all' => 'Hello,',

        'status_update' => [
            'subject' => ':name — update from :application',
            'lines' => [
                'This is the scheduled update for :name.',
                'Nothing here needs a reply unless something looks wrong to you.',
            ],
        ],

        'action_required' => [
            'subject' => ':name needs attention — :application',
            'lines' => [
                'This is the scheduled note for :name, and it needs somebody to pick it up.',
                'Please take a look and reply once it has been dealt with.',
            ],
        ],

        'blank' => [
            'empty' => 'This notification was sent without a message.',
        ],

        'general_reminder' => [
            'subject' => 'A reminder from :application',
            'lines' => [
                'This is a scheduled reminder from :application.',
                'If anything needs your attention, simply reply to this email and we will pick it up.',
            ],
        ],

        'monthly_reminder' => [
            'subject' => 'Your monthly update from :application',
            'lines' => [
                'Here is your monthly note from :application.',
                'Nothing is required from you right now. Reply to this email if you would like to go through anything together.',
            ],
        ],

        'annual_reminder' => [
            'subject' => 'Your annual reminder from :application',
            'lines' => [
                'This is your yearly reminder from :application.',
                'It is a good moment to check that everything we look after on your behalf is still as you want it.',
                'Reply to this email and we will arrange a review.',
            ],
        ],

    ],

];
