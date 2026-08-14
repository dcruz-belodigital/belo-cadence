<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Rules\EmailAddressRule;
use App\ValueObjects\EmailAddress;
use Illuminate\Foundation\Http\FormRequest;

final class PasswordResetLinkRequest extends FormRequest
{
    /**
     * @return array<string, list<string|EmailAddressRule>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', new EmailAddressRule],
        ];
    }

    public function emailAddress(): EmailAddress
    {
        return new EmailAddress($this->string('email')->toString());
    }
}
