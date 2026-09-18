<?php

declare(strict_types=1);

use App\Enums\ColorScheme;
use App\Enums\Locale;
use App\Models\Client;
use App\Models\NotificationSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

describe('the profile page', function (): void {
    it('shows the signed-in person their account and preferences', function (): void {
        $user = administrator(['name' => 'Ada Demo']);

        actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Ada Demo')
            ->assertSee($user->email->value)
            ->assertSee(__('profile.sections.preferences'));
    });

    it('updates the name', function (): void {
        $user = administrator(['name' => 'Old Name']);

        actingAs($user)
            ->put(route('profile.update'), ['name' => 'New Name'])
            ->assertRedirect();

        expect($user->fresh()->name)->toBe('New Name');
    });
});

describe('preferences', function (): void {
    it('stores the theme, language and timezone', function (): void {
        $user = administrator();

        actingAs($user)
            ->put(route('profile.preferences.update'), [
                'color_scheme' => ColorScheme::Dark->value,
                'locale' => Locale::English->value,
                'timezone' => 'Europe/Lisbon',
            ])
            ->assertRedirect();

        $user->refresh();

        expect($user->color_scheme)->toBe(ColorScheme::Dark)
            ->and($user->locale)->toBe(Locale::English)
            ->and($user->timezone->value)->toBe('Europe/Lisbon');
    });

    it('applies the stored theme to the page without a flash of the other one', function (): void {
        $user = administrator(['color_scheme' => ColorScheme::Dark]);

        actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('const preference = "dark"', escape: false);
    });

    it('shows dates in the stored timezone', function (): void {
        $tokyo = administrator(['timezone' => 'Asia/Tokyo']);

        $client = Client::factory()->create();

        $schedule = NotificationSchedule::factory()
            ->for($client)
            ->scheduledFor(CarbonImmutable::parse('2026-06-01 22:00', 'UTC'))
            ->create();

        actingAs($tokyo)
            ->get(route('cadence.schedules.show', $schedule))
            ->assertOk()
            // 22:00 UTC is 07:00 the next morning in Tokyo.
            ->assertSee('02 Jun 2026, 07:00')
            ->assertSee('Asia/Tokyo');
    });

    it('rejects an unknown theme, language or timezone', function (array $payload, string $field): void {
        actingAs(administrator())
            ->put(route('profile.preferences.update'), $payload)
            ->assertSessionHasErrors($field);
    })->with([
        'color_scheme' => [['color_scheme' => 'neon', 'locale' => 'en', 'timezone' => 'UTC'], 'color_scheme'],
        'locale' => [['color_scheme' => 'dark', 'locale' => 'martian', 'timezone' => 'UTC'], 'locale'],
        'timezone' => [['color_scheme' => 'dark', 'locale' => 'en', 'timezone' => 'Mars/Olympus'], 'timezone'],
    ]);

    it('changes only the theme from the top bar switcher', function (): void {
        $user = administrator(['color_scheme' => ColorScheme::System, 'timezone' => 'Europe/Lisbon']);

        actingAs($user)
            ->patch(route('profile.color-scheme.update'), ['color_scheme' => ColorScheme::Light->value])
            ->assertRedirect();

        $user->refresh();

        expect($user->color_scheme)->toBe(ColorScheme::Light)
            ->and($user->timezone->value)->toBe('Europe/Lisbon');
    });

    it('always applies preferences to the signed-in person', function (): void {
        $user = administrator(['color_scheme' => ColorScheme::System]);
        $other = User::factory()->create(['color_scheme' => ColorScheme::System]);

        actingAs($user)->put(route('profile.preferences.update'), [
            'color_scheme' => ColorScheme::Dark->value,
            'locale' => Locale::English->value,
            'timezone' => 'UTC',
            // A payload trying to aim at somebody else.
            'user_id' => $other->getKey(),
            'id' => $other->getKey(),
        ]);

        expect($user->fresh()->color_scheme)->toBe(ColorScheme::Dark)
            ->and($other->fresh()->color_scheme)->toBe(ColorScheme::System);
    });
});

describe('changing your own password', function (): void {
    it('replaces the password when the current one is given', function (): void {
        $user = administrator();

        actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'my-brand-new-password-1',
                'password_confirmation' => 'my-brand-new-password-1',
            ])
            ->assertRedirect();

        expect(Hash::check('my-brand-new-password-1', $user->fresh()->password))->toBeTrue();
    });

    it('refuses a wrong current password', function (): void {
        $user = administrator();

        actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'my-brand-new-password-1',
                'password_confirmation' => 'my-brand-new-password-1',
            ])
            ->assertSessionHasErrors('current_password');

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    });

    it('refuses a weak new password', function (): void {
        actingAs(administrator())
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    });
});
