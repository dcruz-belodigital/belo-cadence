<?php

declare(strict_types=1);

namespace App\Data\Users;

use App\ValueObjects\EmailAddress;

final readonly class CreateUserData
{
    /**
     * @param  list<string>  $roleNames
     */
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public string $password,
        public bool $isActive,
        public array $roleNames = [],
    ) {}
}
