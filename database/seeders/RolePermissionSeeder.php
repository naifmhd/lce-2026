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
        Permission::ViewCouncilPledge,
        Permission::UpdateCouncilPledge,
        Permission::ViewMayorPledge,
        Permission::UpdateMayorPledge,
        Permission::ViewWdcPledge,
        Permission::UpdateWdcPledge,
        Permission::ViewRaeesaPledge,
        Permission::UpdateRaeesaPledge,
    ];

    public function run(): void
    {
        RolePermission::truncate();

        $defaults = [
            // Full-access roles — all pledge permissions
            UserRole::Admin->value => self::ALL_PERMISSIONS,
            UserRole::CallCenter->value => self::ALL_PERMISSIONS,

            // Role-specific access
            UserRole::Mayor->value => [Permission::ViewMayorPledge, Permission::UpdateMayorPledge],
            UserRole::Raeesa->value => [Permission::ViewRaeesaPledge, Permission::UpdateRaeesaPledge],

            // Council roles — council pledge only
            UserRole::Dhaaira1Council->value => [Permission::ViewCouncilPledge, Permission::UpdateCouncilPledge],
            UserRole::Dhaaira2Council->value => [Permission::ViewCouncilPledge, Permission::UpdateCouncilPledge],
            UserRole::Dhaaira3Council->value => [Permission::ViewCouncilPledge, Permission::UpdateCouncilPledge],
            UserRole::Dhaaira4Council->value => [Permission::ViewCouncilPledge, Permission::UpdateCouncilPledge],
            UserRole::Dhaaira5Council->value => [Permission::ViewCouncilPledge, Permission::UpdateCouncilPledge],
            UserRole::Dhaaira6Council->value => [Permission::ViewCouncilPledge, Permission::UpdateCouncilPledge],

            // WDC roles — WDC pledge only
            UserRole::Dhaaira1Wdc->value => [Permission::ViewWdcPledge, Permission::UpdateWdcPledge],
            UserRole::Dhaaira2Wdc->value => [Permission::ViewWdcPledge, Permission::UpdateWdcPledge],
            UserRole::Dhaaira3Wdc->value => [Permission::ViewWdcPledge, Permission::UpdateWdcPledge],
            UserRole::Dhaaira4Wdc->value => [Permission::ViewWdcPledge, Permission::UpdateWdcPledge],
            UserRole::Dhaaira5Wdc->value => [Permission::ViewWdcPledge, Permission::UpdateWdcPledge],
            UserRole::Dhaaira6Wdc->value => [Permission::ViewWdcPledge, Permission::UpdateWdcPledge],
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
