<?php

declare(strict_types=1);

return [

    'title' => 'Client attributes',
    'description' => 'The extra fields your team records about a client. Active ones appear on the client form.',

    'columns' => [
        'clients' => 'In use by',
        'name' => 'Name',
        'position' => 'Order',
        'required' => 'Required',
        'state' => 'State',
        'type' => 'Type',
    ],

    'fields' => [
        'field_name' => 'Field name',
        'field_required' => 'Required',
        'field_type' => 'Field type',
        'fields' => 'Fields in each row',
        'hint' => 'Helper text',
        'is_active' => 'Active',
        'is_required' => 'Required',
        'key' => 'Identifier',
        'name' => 'Name',
        'options' => 'Options',
        'position' => 'Order',
        'type' => 'Type',
    ],

    'hints' => [
        'fields' => 'Each row of this attribute is made of these fields. A field can itself be a set of repeating rows.',
        'hint' => 'Shown under the field on the client form. Leave it empty if the name says enough.',
        'is_active' => 'An inactive attribute disappears from the client form, the client page and both CSV files. Nothing already recorded is lost.',
        'is_required' => 'A client cannot be saved without it. Clients created before this was ticked stay as they are until somebody next edits them.',
        'key' => 'Used as the column name in CSV files. It is set from the name when the attribute is created and never changes afterwards.',
        'name' => 'What the field is called on the client form.',
        'options' => 'One option per line. Removing a line stops it being offered, but clients already using it keep their answer.',
        'position' => 'Lower numbers come first, everywhere this attribute appears.',
        'type' => 'The type cannot be changed later, because every answer already recorded is stored in its shape. To change it, deactivate this attribute and create another.',
    ],

    'actions' => [
        'add_field' => 'Add a field',
        'add_row' => 'Add a row',
        'create' => 'New attribute',
        'delete' => 'Delete',
        'remove_field' => 'Remove',
        'remove_row' => 'Remove',
    ],

    'filters' => [
        'search' => 'Search name',
        'state' => 'State',
        'type' => 'Type',
    ],

    'states' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'create' => [
        'title' => 'New client attribute',
        'description' => 'Define a field your team fills in on every client.',
        'submit' => 'Create attribute',
    ],

    'edit' => [
        'title' => 'Edit attribute',
        'description' => 'The name, the helper text and the options can change. The identifier and the type cannot.',
        'submit' => 'Save attribute',
    ],

    'show' => [
        'details' => 'Attribute',
        'fields' => 'Fields in each row',
        'options' => 'Options',
        'retired' => 'no longer offered',
        'usage' => 'Recorded on :count clients.',
        'usage_none' => 'No client has recorded a value for this yet.',
    ],

    'empty' => [
        'title' => 'No attributes yet',
        'description' => 'Create one and it appears on every client form straight away.',
        'filtered_title' => 'No attributes match these filters',
        'filtered_description' => 'Try a different search term or clear the filters.',
    ],

    'delete' => [
        'title' => 'Delete this attribute?',
        'message' => 'Every answer recorded against it is deleted with it, on :count clients, and this cannot be undone. To stop using it without losing anything, deactivate it instead.',
        'confirm' => 'Delete attribute',
    ],

    'client' => [
        'title' => 'Attributes',
        'description' => 'The extra fields your team records about a client.',
        'empty' => 'No attributes have been defined yet.',
        'rows_empty' => 'No rows yet.',
    ],

    'errors' => [
        'duplicate_field_key' => 'Two fields in the same row cannot have the same name.',
        'repeater_needs_field' => 'Repeating rows need at least one field.',
        'reserved_key' => 'That name is already used by one of the built-in client fields.',
        'row' => 'Row :row:',
        'select_needs_option' => 'A choice from a list needs at least one option.',
        'too_deep' => 'Repeating rows cannot be nested more than :levels levels deep.',
    ],

    'flash' => [
        'created' => ':name has been created.',
        'deleted' => ':name has been deleted.',
        'updated' => ':name has been updated.',
    ],

];
