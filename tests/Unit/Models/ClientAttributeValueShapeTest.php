<?php

declare(strict_types=1);

use App\Enums\ClientAttributeType;
use App\Models\ClientAttribute;
use App\ValueObjects\ClientAttributeChoices;
use App\ValueObjects\ClientAttributeField;

/**
 * The machine-readable CSV is the contract that lets a raw export be imported back, so
 * every type has to survive the round trip unchanged — including a repeater inside a
 * repeater, which is the shape with the most ways to go wrong.
 */
function attributeOfType(ClientAttributeType $type): ClientAttribute
{
    return new ClientAttribute([
        'key' => 'sample',
        'name' => 'Sample',
        'type' => $type,
    ]);
}

it('writes a value to a csv cell and reads the same value back', function (ClientAttributeType $type, mixed $value, string $cell): void {
    $attribute = attributeOfType($type);

    expect($attribute->toCsv($value))->toBe($cell)
        ->and($attribute->fromCsv($cell))->toBe($value);
})->with([
    'text' => [ClientAttributeType::Text, 'Northwind Studio', 'Northwind Studio'],
    'long text' => [ClientAttributeType::LongText, "First line\nSecond line", "First line\nSecond line"],
    'whole number' => [ClientAttributeType::Number, 12, '12'],
    'decimal' => [ClientAttributeType::Number, 12.5, '12.5'],
    'date' => [ClientAttributeType::Date, '2026-03-01', '2026-03-01'],
    'true' => [ClientAttributeType::Boolean, true, '1'],
    'false' => [ClientAttributeType::Boolean, false, '0'],
    'select' => [ClientAttributeType::Select, 'Gold', 'Gold'],
    'url' => [ClientAttributeType::Url, 'https://example.test', 'https://example.test'],
    'email' => [ClientAttributeType::Email, 'hello@example.test', 'hello@example.test'],
]);

it('round-trips a repeater, nesting included, through one json cell', function (): void {
    $attribute = attributeOfType(ClientAttributeType::Repeater);

    $rows = [
        ['name' => 'Ana', 'addresses' => [['city' => 'Porto']]],
        ['name' => 'Rui', 'addresses' => []],
    ];

    $cell = $attribute->toCsv($rows);

    expect($cell)->toBe('[{"name":"Ana","addresses":[{"city":"Porto"}]},{"name":"Rui","addresses":[]}]')
        ->and($attribute->fromCsv($cell))->toBe($rows);
});

it('hands a malformed repeater cell back untouched so it can be reported', function (): void {
    expect(attributeOfType(ClientAttributeType::Repeater)->fromCsv('not json at all'))
        ->toBe('not json at all');
});

it('treats a blank cell as no value for every type', function (ClientAttributeType $type): void {
    expect(attributeOfType($type)->fromCsv('  '))->toBeNull();
})->with(ClientAttributeType::cases());

it('reads the spellings a person is likely to type for yes and no', function (string $cell, bool $expected): void {
    expect(attributeOfType(ClientAttributeType::Boolean)->fromCsv($cell))->toBe($expected);
})->with([
    ['1', true],
    ['0', false],
    ['true', true],
    ['FALSE', false],
    ['yes', true],
    ['No', false],
]);

it('refuses to guess at a number it does not understand', function (): void {
    // "1,5" is a decimal in Portuguese and a thousands separator in English, so it is
    // handed back for the rule to reject rather than quietly reinterpreted.
    expect(attributeOfType(ClientAttributeType::Number)->fromCsv('1,5'))->toBe('1,5');
});

it('reads a repeater the way a person does, at every depth', function (): void {
    $attribute = new ClientAttribute([
        'key' => 'contacts',
        'name' => 'Contacts',
        'type' => ClientAttributeType::Repeater,
        'fields' => [
            new ClientAttributeField('name', 'Name', ClientAttributeType::Text),
            new ClientAttributeField('addresses', 'Addresses', ClientAttributeType::Repeater, fields: [
                new ClientAttributeField('city', 'City', ClientAttributeType::Text),
            ]),
        ],
    ]);

    $formatted = $attribute->formatValue([
        ['name' => 'Ana', 'addresses' => [['city' => 'Porto'], ['city' => 'Lisboa']]],
        ['name' => 'Rui', 'addresses' => []],
    ]);

    expect($formatted)->toBe("Name: Ana, Addresses: (City: Porto / City: Lisboa)\nName: Rui");
});

it('reads a stored date in the reader format and a boolean as a word', function (): void {
    expect(attributeOfType(ClientAttributeType::Date)->formatValue('2026-03-01'))->toBe('01 Mar 2026')
        ->and(attributeOfType(ClientAttributeType::Boolean)->formatValue(true))->toBe('Yes')
        ->and(attributeOfType(ClientAttributeType::Boolean)->formatValue(false))->toBe('No');
});

it('retires a choice that is no longer listed instead of losing it', function (): void {
    $choices = ClientAttributeChoices::fromArray(['Gold', 'Silver', 'Bronze']);

    $merged = $choices->mergeLines("Gold\nSilver");

    // Bronze is still valid for a client already holding it, but is no longer offered.
    expect($merged->values())->toBe(['Gold', 'Silver', 'Bronze'])
        ->and($merged->activeValues())->toBe(['Gold', 'Silver'])
        ->and($merged->isRetired('Bronze'))->toBeTrue()
        ->and($merged->toLines())->toBe("Gold\nSilver");
});

it('brings a retired choice back when it is typed again', function (): void {
    $retired = ClientAttributeChoices::fromArray(['Gold', 'Silver'])->mergeLines('Gold');

    expect($retired->mergeLines("Gold\nSilver")->activeValues())->toBe(['Gold', 'Silver']);
});

it('splits options on line breaks alone, so an option may contain spaces', function (): void {
    expect(ClientAttributeChoices::splitLines("In progress\n\n  Done  \nIn progress\n"))
        ->toBe(['In progress', 'Done']);
});
