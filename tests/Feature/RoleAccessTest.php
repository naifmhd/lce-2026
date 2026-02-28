<?php

use App\Enums\UserRole;
use App\Models\User;

test('authenticated user without roles is forbidden from protected pages', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->actingAs($user)->get(route('home'))->assertForbidden();
    $this->actingAs($user)->get(route('voters.index'))->assertForbidden();
    $this->actingAs($user)->get(route('profile.edit'))->assertForbidden();
});

test('authenticated user with a valid role can access voters and home pages', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1->value])->create();

    $this->actingAs($user)->get(route('home'))->assertSuccessful();
    $this->actingAs($user)->get(route('voters.index'))->assertSuccessful();
});
