<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ColorScheme;
use App\Enums\Locale;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the first account able to administer the application.
 *
 * No credential is hardcoded: the password comes from the environment, or a strong
 * one is generated and shown once. Running this again never resets an existing
 * account's password, it only makes sure the administrator role is still attached.
 */
final class InitialAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('cadence.initial_administrator.email');
        $configuredPassword = config('cadence.initial_administrator.password');

        $existing = User::query()->where('email', $email)->first();

        if ($existing instanceof User) {
            $existing->assignRole($this->administratorRole());

            return;
        }

        $password = is_string($configuredPassword) && $configuredPassword !== ''
            ? $configuredPassword
            : Str::password(24);

        $administrator = User::query()->create([
            'name' => (string) config('cadence.initial_administrator.name'),
            'email' => $email,
            'password' => $password,
            'is_active' => true,
            'color_scheme' => ColorScheme::System,
            'locale' => Locale::English,
            'timezone' => (string) config('app.timezone'),
        ]);

        $administrator->assignRole($this->administratorRole());

        if (! is_string($configuredPassword) || $configuredPassword === '') {
            $this->command?->newLine();
            $this->command?->warn('Initial administrator created.');
            $this->command?->line("  Email:    {$email}");
            $this->command?->line("  Password: {$password}");
            $this->command?->warn('Store this password now: it is not shown again.');
            $this->command?->newLine();
        }
    }

    private function administratorRole(): Role
    {
        return Role::findByName((string) config('cadence.administrator_role'), 'web');
    }
}
