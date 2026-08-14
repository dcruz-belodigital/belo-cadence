<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Administrator Role
    |--------------------------------------------------------------------------
    |
    | The role that receives every application permission when the essential
    | seeders run. It is also the role protected against being left without a
    | usable administrator.
    |
    */

    'administrator_role' => 'Administrator',

    /*
    |--------------------------------------------------------------------------
    | Initial Administrator
    |--------------------------------------------------------------------------
    |
    | The first account created by the essential seeders. Leave the password
    | empty and a strong one is generated and printed once during seeding, so no
    | credential is ever committed to the repository.
    |
    */

    'initial_administrator' => [
        'name' => env('CADENCE_ADMIN_NAME', 'Administrator'),
        'email' => env('CADENCE_ADMIN_EMAIL', 'admin@belo-cadence.test'),
        'password' => env('CADENCE_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The theme an environment starts on, before an administrator picks one in
    | the application settings. Must be a value of App\Enums\Theme.
    |
    */

    'default_theme' => env('CADENCE_DEFAULT_THEME', 'iris'),

];
