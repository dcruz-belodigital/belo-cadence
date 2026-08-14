<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

beforeEach(function (): void {
    RateLimiter::clear('');
});

it('locks out after five wrong passwords and says how long to wait', function (): void {
    Event::fake([Lockout::class]);

    $user = User::factory()->create();

    foreach (range(1, 5) as $ignored) {
        post(route('login.store'), [
            'email' => $user->email->value,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    $response = post(route('login.store'), [
        'email' => $user->email->value,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Too many sign-in attempts');

    Event::assertDispatched(Lockout::class);

    // Even the correct password is refused while the lockout lasts.
    post(route('login.store'), [
        'email' => $user->email->value,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    assertGuest();
});

it('forgets the failed attempts once a sign-in succeeds', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 3) as $ignored) {
        post(route('login.store'), [
            'email' => $user->email->value,
            'password' => 'wrong-password',
        ]);
    }

    post(route('login.store'), [
        'email' => $user->email->value,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    post(route('logout'));

    // The counter was cleared, so a fresh run of wrong attempts is allowed again.
    foreach (range(1, 4) as $ignored) {
        post(route('login.store'), [
            'email' => $user->email->value,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    expect(session('errors')->first('email'))->not->toContain('Too many sign-in attempts');
});

it('counts attempts per account, so one account cannot lock out another', function (): void {
    $victim = User::factory()->create();
    $attacked = User::factory()->create();

    foreach (range(1, 6) as $ignored) {
        post(route('login.store'), [
            'email' => $attacked->email->value,
            'password' => 'wrong-password',
        ]);
    }

    post(route('login.store'), [
        'email' => $victim->email->value,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});
