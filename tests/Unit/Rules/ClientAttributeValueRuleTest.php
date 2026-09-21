<?php

declare(strict_types=1);

use App\Enums\ClientAttributeType;
use App\Models\ClientAttribute;
use App\Rules\ClientAttributeValueRule;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;
use Illuminate\Support\Facades\Validator;

/**
 * One rule validates every shape an answer can take, at any depth, because the client
 * form and the CSV import both go through it. A disagreement between the two would let
 * an import write something the form would have refused.
 *
 * @param  array<string, mixed>  $overrides
 * @return list<string>
 */
function failuresFor(array $overrides, mixed $value): array
{
    $attribute = new ClientAttribute([
        'key' => 'sample',
        'name' => 'Sample',
        'type' => ClientAttributeType::Text,
        'is_required' => false,
        ...$overrides,
    ]);

    $validator = Validator::make(
        ['answer' => $value],
        ['answer' => ClientAttributeValueRule::for($attribute)],
    );

    return $validator->errors()->get('answer');
}

it('accepts a well-formed value of every basic type', function (ClientAttributeType $type, mixed $value): void {
    expect(failuresFor(['type' => $type], $value))->toBe([]);
})->with([
    'text' => [ClientAttributeType::Text, 'Northwind Studio'],
    'long text' => [ClientAttributeType::LongText, "A note\n\nAnd another"],
    'whole number' => [ClientAttributeType::Number, 12],
    'decimal' => [ClientAttributeType::Number, 12.5],
    'date' => [ClientAttributeType::Date, '2026-03-01'],
    'true' => [ClientAttributeType::Boolean, true],
    'false' => [ClientAttributeType::Boolean, false],
    'url' => [ClientAttributeType::Url, 'https://example.test/path'],
    'email' => [ClientAttributeType::Email, 'hello@example.test'],
]);

it('refuses a value that is not of its type', function (ClientAttributeType $type, mixed $value): void {
    expect(failuresFor(['type' => $type], $value))->not->toBe([]);
})->with([
    'a word where a number belongs' => [ClientAttributeType::Number, 'about twelve'],
    'a loose date' => [ClientAttributeType::Date, 'next tuesday'],
    'a date with a time' => [ClientAttributeType::Date, '2026-03-01 09:00'],
    'a bare hostname' => [ClientAttributeType::Url, 'not a url'],
    'an address with no domain' => [ClientAttributeType::Email, 'hello@'],
    'text past the limit' => [ClientAttributeType::Text, str_repeat('a', 256)],
]);

it('lets an optional attribute go unanswered but not a required one', function (): void {
    expect(failuresFor(['is_required' => false], null))->toBe([])
        ->and(failuresFor(['is_required' => true], null))->not->toBe([])
        ->and(failuresFor(['is_required' => true], '   '))->not->toBe([]);
});

it('treats an empty repeater as no answer at all', function (): void {
    $repeater = ['type' => ClientAttributeType::Repeater, 'fields' => [
        new ClientAttributeField('name', 'Name', ClientAttributeType::Text),
    ]];

    expect(failuresFor($repeater, []))->toBe([])
        ->and(failuresFor([...$repeater, 'is_required' => true], []))->not->toBe([]);
});

it('accepts every choice it has ever offered, including a retired one', function (): void {
    $select = [
        'type' => ClientAttributeType::Select,
        'options' => ClientAttributeChoices::fromArray(['Gold', 'Silver'])->mergeLines('Gold'),
    ];

    // Silver is no longer offered, but a client still holding it stays editable.
    expect(failuresFor($select, 'Gold'))->toBe([])
        ->and(failuresFor($select, 'Silver'))->toBe([])
        ->and(failuresFor($select, 'Platinum'))->not->toBe([]);
});

it('names the row a repeater went wrong on', function (): void {
    $failures = failuresFor([
        'name' => 'Contacts',
        'type' => ClientAttributeType::Repeater,
        'fields' => [
            new ClientAttributeField('name', 'Name', ClientAttributeType::Text, isRequired: true),
            new ClientAttributeField('extension', 'Extension', ClientAttributeType::Number),
        ],
    ], [
        ['name' => 'Ana', 'extension' => 22],
        ['name' => '', 'extension' => 'not a number'],
    ]);

    expect($failures)->toHaveCount(2)
        ->and($failures[0])->toStartWith('Row 2:')
        ->and(implode(' ', $failures))->toContain('Name')->toContain('Extension');
});

it('reports a field with no name under the name of the attribute holding it', function (): void {
    $failures = failuresFor([
        'name' => 'Domains',
        'type' => ClientAttributeType::Repeater,
        'fields' => [new ClientAttributeField('field_1', '', ClientAttributeType::Url, isRequired: true)],
    ], [['field_1' => null]]);

    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toStartWith('Row 1:')
        ->and($failures[0])->toContain('Domains');
});

it('validates a repeater inside a repeater, and says which row of which', function (): void {
    $failures = failuresFor([
        'name' => 'Contacts',
        'type' => ClientAttributeType::Repeater,
        'fields' => [
            new ClientAttributeField('name', 'Name', ClientAttributeType::Text),
            new ClientAttributeField('addresses', 'Addresses', ClientAttributeType::Repeater, fields: [
                new ClientAttributeField('city', 'City', ClientAttributeType::Text, isRequired: true),
            ]),
        ],
    ], [
        ['name' => 'Ana', 'addresses' => [['city' => 'Porto'], ['city' => null]]],
    ]);

    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toStartWith('Row 1: Row 2:')
        ->and($failures[0])->toContain('City');
});

it('refuses more rows than one repeater may hold', function (): void {
    $rows = array_fill(0, ClientAttributeValueRule::MAX_ROWS + 1, ['name' => 'Ana']);

    expect(failuresFor([
        'type' => ClientAttributeType::Repeater,
        'fields' => [new ClientAttributeField('name', 'Name', ClientAttributeType::Text)],
    ], $rows))->not->toBe([]);
});

it('refuses a repeater cell that never decoded from json', function (): void {
    expect(failuresFor([
        'type' => ClientAttributeType::Repeater,
        'fields' => [new ClientAttributeField('name', 'Name', ClientAttributeType::Text)],
    ], 'not json at all'))->not->toBe([]);
});
