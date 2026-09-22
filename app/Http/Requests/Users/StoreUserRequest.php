<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Data\Users\CreateUserData;
use App\Rules\EmailAddressRule;
use App\ValueObjects\EmailAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', new EmailAddressRule, Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }

    public function toData(): CreateUserData
    {
        /** @var list<string> $roles */
        $roles = $this->validated('roles') ?? [];

        return new CreateUserData(
            name: $this->string('name')->toString(),
            email: new EmailAddress($this->string('email')->toString()),
            password: $this->string('password')->toString(),
            isActive: $this->boolean('is_active'),
            roleNames: array_values($roles),
        );
    }
}
