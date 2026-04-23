<?php

namespace App\Http\Middleware;

use App\Services\ResilientCacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceRefreshCache
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(
            ResilientCacheService::FORCE_REFRESH_ATTRIBUTE,
            filter_var($request->header('X-Force-Refresh', false), FILTER_VALIDATE_BOOLEAN)
        );

        return $next($request);
    }
}
