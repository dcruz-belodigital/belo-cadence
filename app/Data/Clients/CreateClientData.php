<?php

declare(strict_types=1);

namespace App\Data\Clients;

use App\Data\Notifications\NotificationScheduleData;
use App\Enums\ClientStatus;
use App\ValueObjects\EmailAddress;

final readonly class CreateClientData
{
    /**
     * @param  list<NotificationScheduleData>  $notificationSchedules  Schedules to create together with the client.
     */
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public ClientStatus $status,
        public ?string $notes,
        public ClientAttributeValuesData $attributeValues = new ClientAttributeValuesData,
        public array $notificationSchedules = [],
    ) {}
}
