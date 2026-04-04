<?php

use App\Listeners\DeregisterSession;
use App\Listeners\EnforceSessionLimit;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

// --- EnforceSessionLimit ---

it('registers a session entry on login', function () {
    $user = User::factory()->create();

    (new EnforceSessionLimit)->handle(new Login('web', $user, false));

    expect(UserSession::where('user_id', $user->id)->count())->toBe(1);
});

it('evicts the oldest session when limit is exceeded', function () {
    Config::set('auth.concurrent_session_limit_behavior', 'evict');

    $user = User::factory()->create();

    UserSession::create([
        'user_id' => $user->id,
        'session_id' => 'oldest-session',
        'last_activity_at' => now()->subMinutes(5),
    ]);

    UserSession::create([
        'user_id' => $user->id,
        'session_id' => 'second-session',
        'last_activity_at' => now()->subMinutes(2),
    ]);

    (new EnforceSessionLimit)->handle(new Login('web', $user, false));

    // oldest evicted; second + new remain
    expect(UserSession::where('session_id', 'oldest-session')->exists())->toBeFalse();
    expect(UserSession::where('session_id', 'second-session')->exists())->toBeTrue();
    expect(UserSession::where('user_id', $user->id)->count())->toBe(2);
});

it('purges stale sessions before counting active ones', function () {
    $user = User::factory()->create();
    $lifetime = (int) config('session.lifetime');

    UserSession::create([
        'user_id' => $user->id,
        'session_id' => 'stale-a',
        'last_activity_at' => now()->subMinutes($lifetime + 1),
    ]);

    UserSession::create([
        'user_id' => $user->id,
        'session_id' => 'stale-b',
        'last_activity_at' => now()->subMinutes($lifetime + 2),
    ]);

    (new EnforceSessionLimit)->handle(new Login('web', $user, false));

    // Both stale sessions purged; only the new one remains
    expect(UserSession::where('user_id', $user->id)->count())->toBe(1);
});

// --- DeregisterSession ---

it('removes the session entry on logout', function () {
    $user = User::factory()->create();
    $sessionId = session()->getId();

    UserSession::create([
        'user_id' => $user->id,
        'session_id' => $sessionId,
        'last_activity_at' => now(),
    ]);

    (new DeregisterSession)->handle(new Logout('web', $user));

    expect(UserSession::where('session_id', $sessionId)->exists())->toBeFalse();
});

// --- SessionController ---

it('redirects guests away from sessions page', function () {
    $this->get(route('sessions.index'))->assertRedirect(route('login'));
});

it('forbids non-admin users from viewing sessions', function () {
    $user = User::factory()->withoutRoles()->create();
    $this->actingAs($user)->get(route('sessions.index'))->assertForbidden();
});

it('allows admin to view sessions page', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin)->get(route('sessions.index'))->assertOk();
});

it('allows admin to force logout a session', function () {
    $admin = User::factory()->create();

    $target = User::factory()->create();
    $session = UserSession::create([
        'user_id' => $target->id,
        'session_id' => 'target-session-id',
        'last_activity_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('sessions.destroy', $session))
        ->assertRedirect();

    expect(UserSession::find($session->id))->toBeNull();
});
