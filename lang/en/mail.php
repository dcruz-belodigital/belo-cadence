<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Client Email Copy
    |--------------------------------------------------------------------------
    |
    | The wording of the source-controlled client email templates. Adding a
    | template means adding a section here as well.
    |
    */

    'client_notifications' => [

        'greeting' => 'Hello :name,',
        'closing' => 'Kind regards,',
        'footer' => 'You are receiving this message because :application sends it as part of an agreed schedule.',
        'scheduled_for' => 'Scheduled for :date',

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
