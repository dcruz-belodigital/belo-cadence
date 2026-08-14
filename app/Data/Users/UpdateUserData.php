<?php

declare(strict_types=1);

namespace App\Data\Users;

use App\ValueObjects\EmailAddress;

final readonly class UpdateUserData
{
    /**
     * @param  list<string>  $roleNames
     * @param  string|null  $password  A new password, or null to keep the current one.
     */
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public ?string $password,
        public array $roleNames = [],
    ) {}
}
