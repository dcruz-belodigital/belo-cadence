<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\ValueObjects\EmailAddress;

/**
 * Turns a typed block of addresses into a list validation and the domain can both use.
 *
 * Recipients are entered as free text — one address per line — because that is how a
 * list of addresses arrives from somewhere else. They reach validation as an array so
 * each address can be reported on its own line rather than as one opaque failure.
 */
trait ParsesRecipientLists
{
    /**
     * @return list<string>
     */
    protected function splitRecipients(string $recipients): array
    {
        $parts = preg_split('/[\s,;]+/', trim($recipients)) ?: [];

        return array_values(array_unique(array_filter(
            array_map('trim', $parts),
            static fn (string $address): bool => $address !== '',
        )));
    }

    /**
     * The submitted recipients as value objects, de-duplicated and normalised.
     *
     * @return list<EmailAddress>
     */
    protected function recipientAddresses(): array
    {
        $recipients = $this->input('recipients');

        if (! is_array($recipients)) {
            return [];
        }

        $addresses = [];

        foreach ($recipients as $address) {
            if (is_string($address) && trim($address) !== '') {
                $email = new EmailAddress($address);

                $addresses[$email->value] = $email;
            }
        }

        return array_values($addresses);
    }

    protected function mergeSplitRecipients(): void
    {
        $recipients = $this->input('recipients');

        if (is_string($recipients)) {
            $this->merge(['recipients' => $this->splitRecipients($recipients)]);
        }
    }
}
