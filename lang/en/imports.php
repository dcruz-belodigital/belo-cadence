<?php

declare(strict_types=1);

return [

    'fields' => [
        'file' => 'CSV file',
    ],

    'guide_link' => 'How importing works',

    /*
    | The heading in the user guide this links to. It is the slug of that heading, so it
    | changes with the heading; `LocaleTest` fails the build if the two drift apart.
    */
    'guide_anchor' => 'importing-and-exporting',

    'upload' => [
        'hint' => 'A CSV file. The next step is where the columns are matched, so the order of your columns does not matter.',
        'starting_point' => 'Need a starting point?',
        'template_link' => 'CSV template',
    ],

    'available_columns' => 'Columns this import understands',
    'available_columns_hint' => 'Your file does not need all of them, and does not need to use these names.',

    'identifier_hint' => 'Leave :column empty to create a new record. Fill it with an existing :column to update that record instead.',

    'columns' => [
        'column' => 'Column',
        'example' => 'Example',
        'id' => 'Identifier',
        'optional' => 'Optional',
        'required' => 'Required',
        'requirement' => 'Required?',
    ],

    'mapping' => [
        'title' => 'Match the columns',
        'description' => 'Choose which column of your file provides each piece of information. Columns left as "Not imported" are ignored.',
        'not_imported' => 'Not imported',
        'preview' => 'The first rows of your file',
        'preview_hint' => 'Shown exactly as they were read, so you can check the file lines up before importing.',
        'row_count' => '{0} No rows to import|{1} :count row to import|[2,*] :count rows to import',
        'start_over' => 'Upload a different file',
        'back_to_upload' => 'Upload',
        'submit' => 'Import these rows',
    ],

    'errors' => [
        'duplicate_heading' => 'The column ":heading" is matched to more than one field.',
        'duplicate_in_file' => 'The value :value also appears on line :line of this file.',
        'expired' => 'The uploaded file is no longer available. Please upload it again.',
        'missing_header_row' => 'The file does not start with a header row.',
        'no_rows' => 'The file does not contain any rows to import.',
        'row' => 'Line :line: :message',
        'unmapped_required' => ':column is required, so it needs a column from your file.',
        'unreadable' => 'The uploaded file could not be read.',
    ],

    'flash' => [
        'clients' => 'Import finished: :created clients created, :updated updated.',
        'users' => 'Import finished: :created users created, :updated updated.',
    ],

    'clients' => [
        'title' => 'Import clients',
        'description' => 'Create or update clients from a CSV file.',
        'submit' => 'Continue',
    ],

    'users' => [
        'title' => 'Import users',
        'description' => 'Create or update user accounts from a CSV file.',
        'submit' => 'Continue',
        'password_hint' => 'New users need a password of at least 12 characters containing letters and numbers. Leave the password empty when updating an existing user to keep their current one.',
        'roles_hint' => 'List roles separated by a semicolon, for example "Administrator;Coordinator". Roles must already exist.',
    ],

];
