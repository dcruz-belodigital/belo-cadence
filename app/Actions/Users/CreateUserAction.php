<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Audits\RecordAuditAction;
use App\Data\Audits\RecordAuditData;
use App\Data\Users\CreateUserData;
use App\Enums\AuditAction;
use App\Enums\ColorScheme;
use App\Models\ApplicationSettings;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a user account.
 *
 * New accounts start with the application's default locale and timezone; the person
 * can change their own preferences afterwards.
 */
final class CreateUserAction
{
    public function __construct(
        private readonly RecordAuditAction $recordAudit,
    ) {}

    public function __invoke(CreateUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $settings = ApplicationSettings::current();

            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'is_active' => $data->isActive,
                'color_scheme' => ColorScheme::System,
                'locale' => $settings->default_locale,
                'timezone' => $settings->default_timezone,
            ]);

            $user->syncRoles($data->roleNames);

            ($this->recordAudit)(new RecordAuditData(
                action: AuditAction::UserCreated,
                auditable: $user,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email->value,
                    'is_active' => $user->is_active,
                    'roles' => $data->roleNames,
                ],
            ));

            return $user;
        });
    }
}
