<?php

declare(strict_types=1);

namespace App\Data\Clients;

use App\Enums\ClientStatus;
use App\ValueObjects\EmailAddress;

final readonly class UpdateClientData
{
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public ClientStatus $status,
        public ?string $notes,
    ) {}
}
