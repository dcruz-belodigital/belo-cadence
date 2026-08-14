<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ColorScheme;
use App\Enums\Locale;
use App\Enums\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The password every generated user signs in with, hashed once per run.
     */
    public const PASSWORD = 'password';

    protected static ?string $hashedPassword = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => self::$hashedPassword ??= Hash::make(self::PASSWORD),
            'is_active' => true,
            'color_scheme' => ColorScheme::System,
            'theme' => null,
            'locale' => Locale::English,
            'timezone' => 'UTC',
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    public function colorScheme(ColorScheme $colorScheme): static
    {
        return $this->state(fn (array $attributes): array => ['color_scheme' => $colorScheme]);
    }

    public function theme(?Theme $theme): static
    {
        return $this->state(fn (array $attributes): array => ['theme' => $theme]);
    }

    public function timezone(string $timezone): static
    {
        return $this->state(fn (array $attributes): array => ['timezone' => $timezone]);
    }
}
