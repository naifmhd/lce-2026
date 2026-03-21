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
        $allRoles = UserRole::options();
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
        if (! in_array($role, UserRole::keys(), true)) {
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
