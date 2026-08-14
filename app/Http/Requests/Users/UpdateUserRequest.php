<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Data\Users\UpdateUserData;
use App\Models\User;
use App\Rules\EmailAddressRule;
use App\Support\EssentialAdministration;
use App\ValueObjects\EmailAddress;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
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
                Rule::unique('users', 'email')->ignore($this->targetUser()->getKey()),
            ],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'roles' => ['array', $this->keepsAnAdministrator()],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }

    public function toData(): UpdateUserData
    {
        /** @var list<string> $roles */
        $roles = $this->validated('roles') ?? [];

        return new UpdateUserData(
            name: $this->string('name')->toString(),
            email: new EmailAddress($this->string('email')->toString()),
            password: $this->filled('password') ? $this->string('password')->toString() : null,
            roleNames: array_values($roles),
        );
    }

    /**
     * Refuses a role change that would leave nobody able to manage users and roles.
     */
    private function keepsAnAdministrator(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $roles = is_array($value) ? array_values(array_filter($value, 'is_string')) : [];

            $wouldLockEveryoneOut = $this->container
                ->make(EssentialAdministration::class)
                ->userRoleChangeWouldLeaveNoAdministrator($this->targetUser(), $roles);

            if ($wouldLockEveryoneOut) {
                $fail(__('users.errors.last_administrator'));
            }
        };
    }

    private function targetUser(): User
    {
        $user = $this->route('user');

        assert($user instanceof User);

        return $user;
    }
}
