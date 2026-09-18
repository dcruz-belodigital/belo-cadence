<?php

declare(strict_types=1);

return [

    'title' => 'Profile',
    'description' => 'Your account and how the application looks to you.',

    'sections' => [
        'account' => 'Account',
        'account_description' => 'Your name as other people see it.',
        'password' => 'Password',
        'password_description' => 'Choose something you do not use anywhere else.',
        'preferences' => 'Preferences',
        'preferences_description' => 'These settings only affect your own view of Belo Cadence.',
        'roles' => 'Your roles',
        'roles_description' => 'Roles and activation are managed by an administrator.',
    ],

    'fields' => [
        'current_password' => 'Current password',
        'email' => 'Email address',
        'locale' => 'Language',
        'name' => 'Full name',
        'password' => 'New password',
        'password_confirmation' => 'Confirm new password',
        'theme' => 'Theme',
        'timezone' => 'Timezone',
    ],

    'hints' => [
        'email' => 'Ask an administrator to change your email address.',
        'theme' => 'Only you see this. Leave it unset to follow the application, which is currently :theme.',
        'timezone' => 'All dates and times in the application are shown, and entered, in this timezone.',
    ],

    'theme_default' => 'Use the application theme',

    'submit' => [
        'account' => 'Save name',
        'password' => 'Change password',
        'preferences' => 'Save preferences',
    ],

    'no_roles' => 'You have no roles yet, so most of the application is not available to you.',

    'flash' => [
        'password_updated' => 'Your password has been changed.',
        'preferences_updated' => 'Your preferences have been saved.',
        'updated' => 'Your profile has been saved.',
    ],

];
