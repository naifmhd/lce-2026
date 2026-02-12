<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('admin user seeder creates expected user', function () {
    $this->seed(AdminUserSeeder::class);

    $user = User::query()
        ->where('email', 'naifmhd@gmail.com')
        ->first();

    expect($user)->not->toBeNull();
    expect($user?->name)->toBe('Naif Mohamed');
    expect($user?->email_verified_at)->not->toBeNull();
    expect(Hash::check('password', (string) $user?->password))->toBeTrue();
});
