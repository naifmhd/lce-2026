<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCandidatesPermission
{
    public function __construct(private readonly RolePermissionService $rolePermissionService) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (! $user->hasFullVoterAccess() && ! $this->rolePermissionService->userHasPermission($user, Permission::Candidates))) {
            abort(403);
        }

        return $next($request);
    }
}
