<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('reaching the application', function (): void {
    it('sends a guest from the root url to the sign-in page', function (): void {
        get('/')->assertRedirect(route('login'));
    });

    it('sends a signed-in user from the root url to the dashboard', function (): void {
        actingAs(administrator())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    });

    it('does not offer public registration', function (string $path): void {
        get($path)->assertNotFound();
    })->with(['/register', '/sign-up']);

    it('keeps every application route away from guests', function (string $routeName): void {
        get(route($routeName))->assertRedirect(route('login'));
    })->with([
        'dashboard',
        'clients.index',
        'cadence.upcoming',
        'cadence.schedules.index',
        'cadence.deliveries.index',
        'admin.users.index',
        'admin.roles.index',
        'admin.audit-log.index',
        'admin.settings.edit',
        'admin.settings.edit',
        'profile.edit',
        'notifications.index',
    ]);
});

describe('signing in', function (): void {
    it('shows the sign-in page to a guest', function (): void {
        get(route('login'))
            ->assertOk()
            ->assertSee(__('auth.login.submit'));
    });

    it('lets an active user sign in', function (): void {
        $user = User::factory()->create();

        post(route('login.store'), [
            'email' => $user->email->value,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        assertAuthenticated();
    });

    it('accepts the email address in any casing', function (): void {
        $user = User::factory()->create(['email' => 'person@example.com']);

        post(route('login.store'), [
            'email' => 'PERSON@Example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        assertAuthenticated();
    });

    it('refuses a wrong password', function (): void {
        $user = User::factory()->create();

        post(route('login.store'), [
            'email' => $user->email->value,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        assertGuest();
    });

    it('refuses a deactivated account', function (): void {
        $user = User::factory()->inactive()->create();

        post(route('login.store'), [
            'email' => $user->email->value,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        assertGuest();
    });

    it('signs out a user who was deactivated during their session', function (): void {
        $user = administrator();

        actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['is_active' => false]);

        actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

        assertGuest();
    });

    it('sends a signed-in user away from the sign-in page', function (): void {
        actingAs(administrator())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    });

    it('lets a user sign out', function (): void {
        actingAs(administrator())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        assertGuest();
    });
});

describe('resetting a password', function (): void {
    it('shows the request page', function (): void {
        get(route('password.request'))->assertOk();
    });

    it('emails a reset link to an active user', function (): void {
        Notification::fake();

        $user = User::factory()->create();

        post(route('password.email'), ['email' => $user->email->value])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('does not email a reset link to a deactivated user', function (): void {
        Notification::fake();

        $user = User::factory()->inactive()->create();

        post(route('password.email'), ['email' => $user->email->value])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    });

    it('lets a user choose a new password with a valid token', function (): void {
        Notification::fake();

        $user = User::factory()->create();

        post(route('password.email'), ['email' => $user->email->value]);

        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        post(route('password.store'), [
            'token' => $token,
            'email' => $user->email->value,
            'password' => 'a-brand-new-password-1',
            'password_confirmation' => 'a-brand-new-password-1',
        ])->assertRedirect(route('login'));

        post(route('login.store'), [
            'email' => $user->email->value,
            'password' => 'a-brand-new-password-1',
        ])->assertRedirect(route('dashboard'));

        assertAuthenticated();
    });
});
