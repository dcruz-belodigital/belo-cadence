<?php

declare(strict_types=1);

namespace App\Data\Clients;

use App\Data\ClientNotifications\ClientNotificationScheduleData;
use App\Enums\ClientStatus;
use App\ValueObjects\EmailAddress;

final readonly class CreateClientData
{
    /**
     * @param  list<ClientNotificationScheduleData>  $notificationSchedules  Schedules to create together with the client.
     */
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public ClientStatus $status,
        public ?string $notes,
        public array $notificationSchedules = [],
    ) {}
}
