<?php

declare(strict_types=1);

return [

    'title' => 'Roles',
    'description' => 'Bundles of permissions you assign to people.',

    'columns' => [
        'name' => 'Role',
        'permissions' => 'Permissions',
        'users' => 'Users',
    ],

    'fields' => [
        'name' => 'Role name',
        'permissions' => 'Permissions',
    ],

    'hints' => [
        'permissions' => 'Permissions are part of the application. You choose which ones this role grants.',
    ],

    'actions' => [
        'create' => 'New role',
        'delete' => 'Delete role',
    ],

    'create' => [
        'title' => 'New role',
        'description' => 'Give the role a name and choose what it may do.',
        'submit' => 'Create role',
    ],

    'edit' => [
        'title' => 'Edit role',
        'submit' => 'Save role',
    ],

    'show' => [
        'details' => 'Role',
        'permissions' => 'Permissions',
        'no_permissions' => 'This role grants no permissions yet.',
        'users' => 'People with this role',
        'no_users' => 'Nobody holds this role yet.',
        'in_use_notice' => 'This role is held by :count user(s), so it cannot be deleted until they are moved to another role.',
    ],

    'delete' => [
        'title' => 'Delete this role?',
        'message' => 'The role disappears. Nobody currently holds it, so no permissions change for anyone.',
        'confirm' => 'Delete role',
    ],

    'empty' => [
        'title' => 'No roles yet',
        'description' => 'Create a role to start granting permissions.',
    ],

    'errors' => [
        'last_administrator' => 'This change would leave nobody able to manage users and roles.',
    ],

    'flash' => [
        'created' => 'The :name role has been created.',
        'deleted' => 'The :name role has been deleted.',
        'updated' => 'The :name role has been updated.',
    ],

];
