<?php

namespace App\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait AppliesVoterRoleScope
{
    protected function applyVoterRoleScope(Builder $query, ?User $user): Builder
    {

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasFullVoterAccess()) {
            return $query;
        }

        $monitorRoles = array_values(array_intersect($user->roleKeys(), UserRole::monitorRoleKeys()));

        if ($monitorRoles !== []) {
            $specificBoxes = [];
            $otherDhaairas = [];

            foreach ($monitorRoles as $role) {
                $boxes = UserRole::registeredBoxesForMonitorRole($role);
                if ($boxes !== []) {
                    $specificBoxes = array_merge($specificBoxes, $boxes);
                }
                $dhaaira = UserRole::dhaairaCodeForOtherMonitorRole($role);
                if ($dhaaira !== null) {
                    $otherDhaairas[] = $dhaaira;
                }
            }

            $knownBoxes = UserRole::knownRegisteredBoxes();

            return $query->where(function (Builder $q) use ($specificBoxes, $otherDhaairas, $knownBoxes) {
                if ($specificBoxes !== []) {
                    $q->orWhereIn('registered_box', $specificBoxes);
                }
                if ($otherDhaairas !== []) {
                    $q->orWhere(function (Builder $sub) use ($otherDhaairas, $knownBoxes) {
                        $sub->whereIn('dhaairaa', $otherDhaairas)
                            ->whereNotIn('registered_box', $knownBoxes);
                    });
                }
            });
        }


        $allowedDhaairaaCodes = $user->allowedDhaairaaCodes();

        if ($allowedDhaairaaCodes === []) {
            return $query->whereRaw('1 = 0');
        }
        return $query->whereIn('dhaairaa', $allowedDhaairaaCodes);
    }
}
