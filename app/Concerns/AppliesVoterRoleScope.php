<?php

namespace App\Concerns;

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

        $allowedDhaairaaCodes = $user->allowedDhaairaaCodes();

        if ($allowedDhaairaaCodes === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('dhaairaa', $allowedDhaairaaCodes);
    }
}
