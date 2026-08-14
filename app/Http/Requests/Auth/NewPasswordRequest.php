<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Rules\EmailAddressRule;
use App\ValueObjects\EmailAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class NewPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string|EmailAddressRule|Password>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', new EmailAddressRule],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function emailAddress(): EmailAddress
    {
        return new EmailAddress($this->string('email')->toString());
    }
}
