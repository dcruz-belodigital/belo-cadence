<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ColorScheme;
use App\Enums\Locale;
use App\Enums\Theme;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demonstration accounts covering every role, theme and activation state.
 *
 * The roles come from RoleSeeder; this seeder creates none of its own, so signing in
 * as a demo account exercises the same permissions a real installation ships with.
 *
 * Every account uses the same obvious password so a developer can sign in as anyone.
 */
final class DemoUserSeeder extends Seeder
{
    public const PASSWORD = 'demo-password-123';

    /**
     * @var list<array{name: string, email: string, role: string|null, color_scheme: ColorScheme, theme: Theme|null, timezone: string, active: bool}>
     */
    private const USERS = [
        [
            'name' => 'Ada Demo (administrator)',
            'email' => 'ada@example.test',
            'role' => 'Administrator',
            'color_scheme' => ColorScheme::System,
            'theme' => Theme::Iris,
            'timezone' => 'Europe/Lisbon',
            'active' => true,
        ],
        [
            'name' => 'Bruno Demo (viewer)',
            'email' => 'bruno@example.test',
            'role' => 'Viewer',
            'color_scheme' => ColorScheme::Light,
            'theme' => Theme::Cappuccino,
            'timezone' => 'Europe/Madrid',
            'active' => true,
        ],
        [
            'name' => 'Chidi Demo (viewer)',
            'email' => 'chidi@example.test',
            'role' => 'Viewer',
            'color_scheme' => ColorScheme::Dark,
            'theme' => Theme::Cathode,
            'timezone' => 'America/New_York',
            'active' => true,
        ],
        [
            'name' => 'Dalia Demo (deactivated)',
            'email' => 'dalia@example.test',
            'role' => 'Viewer',
            'color_scheme' => ColorScheme::System,
            'theme' => Theme::Bubblegum,
            'timezone' => 'UTC',
            'active' => false,
        ],
        [
            'name' => 'Eli Demo (no role)',
            'email' => 'eli@example.test',
            'role' => null,
            'color_scheme' => ColorScheme::Light,
            'theme' => null,
            'timezone' => 'Asia/Tokyo',
            'active' => true,
        ],
    ];

    public function run(): void
    {
        $password = Hash::make(self::PASSWORD);

        foreach (self::USERS as $demoUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => $password,
                    'is_active' => $demoUser['active'],
                    'color_scheme' => $demoUser['color_scheme'],
                    'theme' => $demoUser['theme'],
                    'locale' => Locale::English,
                    'timezone' => $demoUser['timezone'],
                ],
            );

            $user->syncRoles($demoUser['role'] === null ? [] : [$demoUser['role']]);
        }

        $this->command?->line('  Demo accounts use the password: '.self::PASSWORD);
    }
}
