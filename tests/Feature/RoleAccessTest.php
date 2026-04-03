<?php

use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;

test('authenticated user without roles is forbidden from protected pages', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->actingAs($user)->get(route('home'))->assertForbidden();
    $this->actingAs($user)->get(route('voters.index'))->assertForbidden();
    $this->actingAs($user)->get(route('profile.edit'))->assertForbidden();
});

test('authenticated user with a valid role can access home page', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1Council->value])->create();

    $this->actingAs($user)->get(route('home'))->assertSuccessful();
});

test('council user with candidates permission can access voters page', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1Council->value])->create();

    RolePermission::create(['role' => UserRole::Dhaaira1Council->value, 'permission' => 'candidates']);

    $this->actingAs($user)->get(route('voters.index'))->assertSuccessful();
});

test('council user without candidates permission cannot access voters page', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1Council->value])->create();

    $this->actingAs($user)->get(route('voters.index'))->assertForbidden();
});
