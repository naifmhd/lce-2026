<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();
});

test('guests are redirected from users page', function () {
    $response = $this->get(route('users.index'));

    $response->assertRedirect(route('login'));
});

test('non-admin users cannot access users page', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1->value])->create();

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertForbidden();
});

test('admin can view users page', function () {
    $admin = User::factory()->withRoles([UserRole::Admin->value])->create();
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Users/Index')
            ->has('users.data')
            ->has('roleOptions', 9),
    );
});

test('admin can create user with multiple roles', function () {
    $admin = User::factory()->withRoles([UserRole::Admin->value])->create();

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Support User',
        'email' => 'support@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [UserRole::CallCenter->value, UserRole::Mayor->value],
    ]);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'support@example.com',
    ]);

    $createdUser = User::query()->where('email', 'support@example.com')->firstOrFail();
    expect($createdUser->roleKeys())->toBe([UserRole::CallCenter->value, UserRole::Mayor->value]);
});

test('admin can update user without changing password', function () {
    $admin = User::factory()->withRoles([UserRole::Admin->value])->create();
    $user = User::factory()->create([
        'password' => 'password',
        'roles' => [UserRole::Dhaaira1->value],
    ]);
    $oldPasswordHash = $user->password;

    $response = $this->actingAs($admin)->patch(route('users.update', $user), [
        'name' => 'Updated Name',
        'email' => $user->email,
        'password' => '',
        'password_confirmation' => '',
        'roles' => [UserRole::Dhaaira2->value],
    ]);

    $response->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->password)->toBe($oldPasswordHash);
    expect($user->roleKeys())->toBe([UserRole::Dhaaira2->value]);
});

test('admin can update user password when provided', function () {
    $admin = User::factory()->withRoles([UserRole::Admin->value])->create();
    $user = User::factory()->create([
        'password' => 'password',
        'roles' => [UserRole::Dhaaira1->value],
    ]);

    $response = $this->actingAs($admin)->patch(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
        'roles' => [UserRole::Dhaaira3->value],
    ]);

    $response->assertRedirect(route('users.index'));

    $user->refresh();
    expect(Hash::check('new-password', $user->password))->toBeTrue();
    expect($user->roleKeys())->toBe([UserRole::Dhaaira3->value]);
});
