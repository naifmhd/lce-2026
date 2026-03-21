<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RolePermissionService
{
    private const CACHE_TTL_MINUTES = 30;

    /**
     * @return array<int, string>
     */
    public function getEnabledPermissions(string $role): array
    {
        return Cache::remember(
            "role_permissions:{$role}",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => RolePermission::where('role', $role)->pluck('permission')->toArray()
        );
    }

    public function userHasPermission(?User $user, Permission $permission): bool
    {
        if ($user === null) {
            return false;
        }

        foreach ($user->roleKeys() as $role) {
            if (in_array($permission->value, $this->getEnabledPermissions($role), true)) {
                return true;
            }
        }

        return false;
    }

    public function clearRoleCache(string $role): void
    {
        Cache::forget("role_permissions:{$role}");
    }

    public function clearAllCache(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->clearRoleCache($role->value);
        }
    }
}
