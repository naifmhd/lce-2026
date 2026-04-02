<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    public function __construct(private readonly RolePermissionService $service) {}

    public function index(): Response
    {
        $allowedRoles = [
            UserRole::Dhaaira1Council->value,
            UserRole::Dhaaira1Wdc->value,
            UserRole::Dhaaira2Council->value,
            UserRole::Dhaaira2Wdc->value,
            UserRole::Dhaaira3Council->value,
            UserRole::Dhaaira3Wdc->value,
            UserRole::Dhaaira4Council->value,
            UserRole::Dhaaira4Wdc->value,
            UserRole::Dhaaira5Council->value,
            UserRole::Dhaaira5Wdc->value,
            UserRole::Dhaaira6Council->value,
            UserRole::Dhaaira6Wdc->value,
            UserRole::Mayor->value,
            UserRole::Raeesa->value,
        ];

        // $allRoles = array_values(array_filter(UserRole::options(), fn (array $r) => $r['key'] !== UserRole::Admin->value));
        $allRoles = array_values(array_filter(
            UserRole::options(),
            fn (array $r) => in_array($r['key'], $allowedRoles, true)
        ));

        $allPermissionKeys = Permission::keys();

        $enabledPermissions = RolePermission::whereIn('role', UserRole::keys())
            ->get()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('permission')->toArray());

        $rolePermissions = array_map(function (array $roleOption) use ($enabledPermissions, $allPermissionKeys): array {
            $role = $roleOption['key'];
            $enabled = $enabledPermissions[$role] ?? [];

            return [
                'role' => $role,
                'label' => $roleOption['label'],
                'permissions' => array_combine(
                    $allPermissionKeys,
                    array_map(fn (string $k) => in_array($k, $enabled, true), $allPermissionKeys)
                ),
            ];
        }, $allRoles);

        return Inertia::render('RolePermissions/Index', [
            'rolePermissions' => $rolePermissions,
            'permissionGroups' => Permission::groups(),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        if (! in_array($role, UserRole::keys(), true) || $role === UserRole::Admin->value) {
            abort(404);
        }

        $validPermissions = Permission::keys();
        $submitted = $request->input('permissions', []);

        if (! is_array($submitted)) {
            $submitted = [];
        }

        RolePermission::where('role', $role)->delete();

        foreach ($submitted as $permKey => $enabled) {
            if ($enabled && is_string($permKey) && in_array($permKey, $validPermissions, true)) {
                RolePermission::create([
                    'role' => $role,
                    'permission' => $permKey,
                ]);
            }
        }

        $this->service->clearRoleCache($role);

        return back();
    }
}
