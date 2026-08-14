<?php

declare(strict_types=1);

use App\Models\User;

it('takes the first and last initial of a name', function (string $name, string $expected): void {
    $user = new User(['name' => $name]);

    expect($user->initials())->toBe($expected);
})->with([
    'two names' => ['Ada Demo', 'AD'],
    'three names' => ['Ada Beatriz Demo', 'AD'],
    'single name' => ['Administrator', 'A'],
    'lower case' => ['ada demo', 'AD'],
    'extra spacing' => ['  Ada   Demo  ', 'AD'],
    'accented' => ['Ália Óscar', 'ÁÓ'],
    'trailing parenthetical' => ['Ada Demo (administrator)', 'AD'],
    'leading symbol' => ['(the) Ada Demo', 'AD'],
    'only symbols' => ['(???)', '?'],
    'empty' => ['', '?'],
]);
