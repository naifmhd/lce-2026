<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isVotersPage = $request->routeIs('voters.*');
        $appearance = $isVotersPage ? 'light' : ($request->cookie('appearance') ?? 'system');

        View::share('appearance', $appearance);

        if ($isVotersPage) {
            Cookie::queue('appearance', 'light', 60 * 24 * 365);
        }

        return $next($request);
    }
}
