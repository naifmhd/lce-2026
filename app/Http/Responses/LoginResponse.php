<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();
        $roleKeys = $user->roleKeys();

        $isOnlyMonitor = count($roleKeys) > 0
            && count(array_diff($roleKeys, UserRole::monitorRoleKeys())) === 0;

        $isOnlyCallCenter = count($roleKeys) > 0
            && count(array_diff($roleKeys, UserRole::ccRoleKeys())) === 0;

        if ($isOnlyMonitor) {
            return redirect()->route('zeroday.index');
        }

        if ($isOnlyCallCenter) {
            return redirect()->route('call-center.index');
        }

        return redirect()->intended(route('home'));
    }
}
