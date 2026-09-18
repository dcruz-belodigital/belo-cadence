<?php

declare(strict_types=1);

return [

    'title' => 'Users',
    'description' => 'The people who can sign in to Belo Cadence.',

    'columns' => [
        'created' => 'Created',
        'email' => 'Email',
        'name' => 'Name',
        'roles' => 'Roles',
        'state' => 'State',
    ],

    'fields' => [
        'email' => 'Email address',
        'is_active' => 'Active',
        'name' => 'Full name',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'roles' => 'Roles',
    ],

    'hints' => [
        'is_active' => 'Only active accounts can sign in.',
        'password_optional' => 'Leave empty to keep the current password.',
        'roles' => 'Permissions come from roles. A user can hold more than one.',
    ],

    'states' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'activate' => 'Activate',
        'create' => 'New user',
        'deactivate' => 'Deactivate',
    ],

    'filters' => [
        'role' => 'Role',
        'search' => 'Search name or email',
        'state' => 'State',
    ],

    'create' => [
        'title' => 'New user',
        'description' => 'There is no public sign-up: accounts are created here.',
        'submit' => 'Create user',
    ],

    'edit' => [
        'title' => 'Edit user',
        'description' => 'Activation is changed from the user page.',
        'submit' => 'Save user',
    ],

    'show' => [
        'details' => 'Account',
        'roles' => 'Roles',
        'no_roles' => 'This user has no roles, so they can only reach their own profile.',
        'inactive_notice' => 'This account is deactivated and cannot sign in.',
    ],

    'activate' => [
        'title' => 'Activate this account?',
        'message' => 'The person will be able to sign in again, with the roles they already have.',
        'confirm' => 'Activate',
    ],

    'deactivate' => [
        'title' => 'Deactivate this account?',
        'message' => 'The person will no longer be able to sign in. Nothing they did is removed, and the account can be activated again later.',
        'confirm' => 'Deactivate',
    ],

    'empty' => [
        'title' => 'No users match these filters',
        'description' => 'Try a different search term or clear the filters.',
    ],

    'errors' => [
        'last_administrator' => 'This change would leave nobody able to manage users and roles.',
    ],

    'flash' => [
        'activated' => ':name can sign in again.',
        'created' => ':name has been created.',
        'deactivated' => ':name has been deactivated.',
        'updated' => ':name has been updated.',
    ],

];
