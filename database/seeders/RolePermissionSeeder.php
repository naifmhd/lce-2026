<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, Permission>
     */
    private const ALL_PERMISSIONS = [
        Permission::CouncilPledge,
        Permission::MayorPledge,
        Permission::WdcPledge,
        Permission::RaeesaPledge,
    ];

    public function run(): void
    {
        RolePermission::truncate();

        $defaults = [
            // Full-access roles — all pledge permissions
            UserRole::Admin->value => self::ALL_PERMISSIONS,

            // Role-specific access
            UserRole::Mayor->value => [Permission::MayorPledge],
            UserRole::Raeesa->value => [Permission::RaeesaPledge],

            // Council roles — council pledge only
            UserRole::Dhaaira1Council->value => [Permission::CouncilPledge],
            UserRole::Dhaaira2Council->value => [Permission::CouncilPledge],
            UserRole::Dhaaira3Council->value => [Permission::CouncilPledge],
            UserRole::Dhaaira4Council->value => [Permission::CouncilPledge],
            UserRole::Dhaaira5Council->value => [Permission::CouncilPledge],
            UserRole::Dhaaira6Council->value => [Permission::CouncilPledge],

            // WDC roles — WDC pledge only
            UserRole::Dhaaira1Wdc->value => [Permission::WdcPledge],
            UserRole::Dhaaira2Wdc->value => [Permission::WdcPledge],
            UserRole::Dhaaira3Wdc->value => [Permission::WdcPledge],
            UserRole::Dhaaira4Wdc->value => [Permission::WdcPledge],
            UserRole::Dhaaira5Wdc->value => [Permission::WdcPledge],
            UserRole::Dhaaira6Wdc->value => [Permission::WdcPledge],
        ];

        foreach ($defaults as $role => $permissions) {
            foreach ($permissions as $permission) {
                RolePermission::create([
                    'role' => $role,
                    'permission' => $permission->value,
                ]);
            }
        }
    }
}
