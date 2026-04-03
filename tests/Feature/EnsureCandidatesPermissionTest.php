<?php

use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;

test('guests are redirected from voters page', function () {
    $this->get(route('voters.index'))->assertRedirect(route('login'));
});

test('users with no roles cannot access voters page', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->actingAs($user)->get(route('voters.index'))->assertForbidden();
});

test('council role can access voters page without explicit candidates permission', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1Council->value])->create();

    $this->actingAs($user)->get(route('voters.index'))->assertOk();
});

test('wdc role can access voters page without explicit candidates permission', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1Wdc->value])->create();

    $this->actingAs($user)->get(route('voters.index'))->assertOk();
});

test('mayor role can access voters page', function () {
    $user = User::factory()->withRoles([UserRole::Mayor->value])->create();

    $this->actingAs($user)->get(route('voters.index'))->assertOk();
});

test('raeesa role can access voters page', function () {
    $user = User::factory()->withRoles([UserRole::Raeesa->value])->create();

    $this->actingAs($user)->get(route('voters.index'))->assertOk();
});

test('admin can access voters page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('voters.index'))->assertOk();
});

test('call center role without candidates permission cannot access voters page', function () {
    $user = User::factory()->withRoles([UserRole::CcDhaaira1->value])->create();

    $this->actingAs($user)->get(route('voters.index'))->assertForbidden();
});

test('call center role with candidates permission can access voters page', function () {
    $user = User::factory()->withRoles([UserRole::CcDhaaira1->value])->create();
    RolePermission::create(['role' => UserRole::CcDhaaira1->value, 'permission' => 'candidates']);

    $this->actingAs($user)->get(route('voters.index'))->assertOk();
});
