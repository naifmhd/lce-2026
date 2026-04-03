<?php

use App\Enums\UserRole;
use App\Models\User;

test('monitor-only user is redirected to zeroday after login', function () {
    $user = User::factory()->withRoles([UserRole::MonitorGreaterMale->value])->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('zeroday.index'));
});

test('call-center-only user is redirected to call center after login', function () {
    $user = User::factory()->withRoles([UserRole::CcDhaaira1->value])->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('call-center.index'));
});

test('user with monitor and other roles is redirected to home after login', function () {
    $user = User::factory()->withRoles([UserRole::MonitorGreaterMale->value, UserRole::Admin->value])->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));
});

test('user with call center and other roles is redirected to home after login', function () {
    $user = User::factory()->withRoles([UserRole::CcDhaaira1->value, UserRole::Dhaaira1Council->value])->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));
});

test('admin is redirected to home after login', function () {
    $user = User::factory()->withRoles([UserRole::Admin->value])->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));
});

test('council user is redirected to home after login', function () {
    $user = User::factory()->withRoles([UserRole::Dhaaira1Council->value])->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));
});
