<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExtraUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // Monitor users
            ['email' => 'mu1@lce.com', 'name' => 'Monitor Uthuru 1', 'role' => UserRole::MonitorUthuru1],
            ['email' => 'mu2@lce.com', 'name' => 'Monitor Uthuru 2', 'role' => UserRole::MonitorUthuru2],
            ['email' => 'mhd1@lce.com', 'name' => 'Monitor Hulhangu Dhekunu 1', 'role' => UserRole::MonitorHulhanguDhekunu1],
            ['email' => 'mhd2@lce.com', 'name' => 'Monitor Hulhangu Dhekunu 2', 'role' => UserRole::MonitorHulhanguDhekunu2],
            ['email' => 'mmu1@lce.com', 'name' => 'Monitor Medhu Uthuru 1', 'role' => UserRole::MonitorMedhuUthuru1],
            ['email' => 'mmu2@lce.com', 'name' => 'Monitor Medhu Uthuru 2', 'role' => UserRole::MonitorMedhuUthuru2],
            ['email' => 'mmd1@lce.com', 'name' => 'Monitor Medhu Dhekunu 1', 'role' => UserRole::MonitorMedhuDhekunu1],
            ['email' => 'mmd2@lce.com', 'name' => 'Monitor Medhu Dhekunu 2', 'role' => UserRole::MonitorMedhuDhekunu2],
            ['email' => 'mid1@lce.com', 'name' => 'Monitor Iru Dhekunu 1', 'role' => UserRole::MonitorIruDhekunu1],
            ['email' => 'mid2@lce.com', 'name' => 'Monitor Iru Dhekunu 2', 'role' => UserRole::MonitorIruDhekunu2],
            ['email' => 'mmale@lce.com', 'name' => 'Monitor Greater Male', 'role' => UserRole::MonitorGreaterMale],

            // Monitor Other
            ['email' => 'mo1@lce.com', 'name' => 'Monitor Other 1', 'role' => UserRole::MonitorOther1],
            ['email' => 'mo2@lce.com', 'name' => 'Monitor Other 2', 'role' => UserRole::MonitorOther2],
            ['email' => 'mo3@lce.com', 'name' => 'Monitor Other 3', 'role' => UserRole::MonitorOther3],
            ['email' => 'mo4@lce.com', 'name' => 'Monitor Other 4', 'role' => UserRole::MonitorOther4],
            ['email' => 'mo5@lce.com', 'name' => 'Monitor Other 5', 'role' => UserRole::MonitorOther5],
            ['email' => 'mo6@lce.com', 'name' => 'Monitor Other 6', 'role' => UserRole::MonitorOther6],

            // Call Center
            ['email' => 'cc1@lce.com', 'name' => 'CC Dhaaira 1', 'role' => UserRole::CcDhaaira1],
            ['email' => 'cc2@lce.com', 'name' => 'CC Dhaaira 2', 'role' => UserRole::CcDhaaira2],
            ['email' => 'cc3@lce.com', 'name' => 'CC Dhaaira 3', 'role' => UserRole::CcDhaaira3],
            ['email' => 'cc4@lce.com', 'name' => 'CC Dhaaira 4', 'role' => UserRole::CcDhaaira4],
            ['email' => 'cc5@lce.com', 'name' => 'CC Dhaaira 5', 'role' => UserRole::CcDhaaira5],
            ['email' => 'cc6@lce.com', 'name' => 'CC Dhaaira 6', 'role' => UserRole::CcDhaaira6],
            ['email' => 'ccm@lce.com', 'name' => 'CC Mayor', 'role' => UserRole::CcMayor],
            ['email' => 'ccr@lce.com', 'name' => 'CC Raeesa', 'role' => UserRole::CcRaeesa],
        ];

        foreach ($users as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'Inthihaabu@2026',
                    'roles' => [$data['role']->value],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
