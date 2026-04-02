<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureZerodayRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $allowedRoles = [UserRole::Admin->value, ...UserRole::monitorRoleKeys()];

        if ($user === null || ! $user->hasAnyRole($allowedRoles)) {
            abort(403);
        }

        return $next($request);
    }
}
