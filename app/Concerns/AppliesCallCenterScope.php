<?php

namespace App\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait AppliesCallCenterScope
{
    protected function applyCcScope(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        $roles = $user->roleKeys();
        $hasCcRole = false;

        $query->where(function (Builder $root) use ($roles, &$hasCcRole): void {
            foreach ($roles as $role) {
                $dhaairaa = UserRole::ccDhaairaCodeForRole($role);

                if ($dhaairaa !== null) {
                    $hasCcRole = true;
                    $root->orWhere(fn (Builder $q) => $q
                        ->where('dhaairaa', $dhaairaa)
                        ->whereHas('pledge', fn (Builder $p) => $p->where(
                            fn (Builder $i) => $i->where('council', 'MDP')->orWhere('wdc', 'MDP')
                        ))
                    );

                    continue;
                }

                if ($role === UserRole::CcMayor->value) {
                    $hasCcRole = true;
                    $root->orWhereHas('pledge', fn (Builder $p) => $p->where('mayor', 'MDP'));

                    continue;
                }

                if ($role === UserRole::CcRaeesa->value) {
                    $hasCcRole = true;
                    $root->orWhereHas('pledge', fn (Builder $p) => $p->where('raeesa', 'MDP'));
                }
            }

            if (! $hasCcRole) {
                $root->whereRaw('1 = 0');
            }
        });

        return $query;
    }
}
