<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use App\Enums\EmailTemplate;
use App\Enums\NotificationTarget;
use App\Models\Client;
use App\Models\NotificationSchedule;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\TemplateBindings;

/**
 * One notification about to go out: which template, what it is about, and every address
 * it is addressed to.
 *
 * Both ways of sending build one of these — the scheduler from a schedule, a person from
 * the send form — so from here on the two paths are identical and there is exactly one
 * definition of "what gets sent to whom".
 */
final readonly class NotificationDispatch
{
    /**
     * @param  list<EmailAddress>  $recipients
     */
    public function __construct(
        public EmailTemplate $template,
        public ?int $clientId,
        public ?string $targetName,
        public array $recipients,
        public ?string $recipientName = null,
        public ?string $subject = null,
        public ?string $message = null,
        public TemplateBindings $templateBindings = new TemplateBindings,
    ) {}

    /**
     * Reads the dispatch off a schedule. The `client` relation has to be loaded.
     */
    public static function forSchedule(NotificationSchedule $schedule): self
    {
        $client = $schedule->client;

        return new self(
            template: $schedule->template,
            clientId: $schedule->client_id,
            targetName: $schedule->displayName(),
            recipients: $schedule->recipientAddresses(),
            recipientName: $client instanceof Client ? $client->name : null,
            subject: $schedule->subject,
            message: $schedule->message,
            templateBindings: $schedule->template_bindings,
        );
    }

    public function target(): NotificationTarget
    {
        return NotificationTarget::fromClientId($this->clientId);
    }
}
