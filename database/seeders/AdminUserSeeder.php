<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'naifmhd@gmail.com'],
            [
                'name' => 'Naif Mohamed',
                'password' => 'password',
                'roles' => [UserRole::Admin->value],
                'email_verified_at' => now(),
            ]
        );
    }
}
