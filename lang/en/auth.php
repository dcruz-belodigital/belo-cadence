<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many sign-in attempts. Please try again in :seconds seconds.',

    'deactivated' => 'This account has been deactivated. Please contact an administrator.',
    'tagline' => 'Recurring client communication, on schedule.',

    'fields' => [
        'email' => 'Email address',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
    ],

    'login' => [
        'title' => 'Sign in',
        'heading' => 'Sign in',
        'subheading' => 'Use your Belo Cadence account to continue.',
        'remember' => 'Keep me signed in',
        'forgot' => 'Forgot your password?',
        'submit' => 'Sign in',
    ],

    'forgot_password' => [
        'title' => 'Reset your password',
        'heading' => 'Reset your password',
        'subheading' => 'We will email you a link to choose a new password.',
        'submit' => 'Email password reset link',
        'back_to_login' => 'Back to sign in',
    ],

    'reset_password' => [
        'title' => 'Choose a new password',
        'heading' => 'Choose a new password',
        'subheading' => 'Pick a password you do not use anywhere else.',
        'submit' => 'Save new password',
    ],

];
