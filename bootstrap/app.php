<?php

use App\Http\Middleware\ForceRefreshCache;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('api', ForceRefreshCache::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'meta' => [
                        'api_version' => 'v1',
                        'pagination' => null,
                        'cache_hit' => false,
                    ],
                    'errors' => [
                        'auth' => ['Unauthenticated.'],
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'meta' => [
                        'api_version' => 'v1',
                        'pagination' => null,
                        'cache_hit' => false,
                    ],
                    'errors' => [
                        'authorization' => ['This action is unauthorized.'],
                    ],
                ], 403);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'meta' => [
                        'api_version' => 'v1',
                        'pagination' => null,
                        'cache_hit' => false,
                    ],
                    'errors' => [
                        'rate_limit' => ['Too many requests. Please try again later.'],
                    ],
                ], 429);
            }
        });
    })->create();
