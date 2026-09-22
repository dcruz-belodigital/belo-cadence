<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Rules\EmailAddressRule;
use App\ValueObjects\EmailAddress;
use Illuminate\Foundation\Http\FormRequest;

final class NewPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string|EmailAddressRule>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', new EmailAddressRule],
            'password' => ['required', 'string', 'confirmed'],
        ];
    }

    public function emailAddress(): EmailAddress
    {
        return new EmailAddress($this->string('email')->toString());
    }
}
