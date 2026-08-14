<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\Data\Clients\UpdateClientData;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Rules\EmailAddressRule;
use App\ValueObjects\EmailAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClientRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'max:255',
                new EmailAddressRule,
                Rule::unique('clients', 'email')->ignore($this->client()->getKey()),
            ],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function toData(): UpdateClientData
    {
        return new UpdateClientData(
            name: $this->string('name')->toString(),
            email: new EmailAddress($this->string('email')->toString()),
            status: ClientStatus::from($this->string('status')->toString()),
            notes: $this->filled('notes') ? $this->string('notes')->toString() : null,
        );
    }

    private function client(): Client
    {
        $client = $this->route('client');

        assert($client instanceof Client);

        return $client;
    }
}
