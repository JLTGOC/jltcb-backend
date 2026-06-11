<?php

use App\Exceptions\InvalidConfigIdsException;
use App\Exceptions\LockedConfigItemException;
use App\Exceptions\VersionConflictException;
use App\Http\Middleware\AllowGuest;
use App\Http\Middleware\RoleBasedSessionTimeout;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Enable stateful API middleware (includes Sanctum support for SPA/token auth)
        $middleware->statefulApi();

        // Core auth/throttle middleware aliases so auth:sanctum works
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'allow.guest' => AllowGuest::class,
            'session.timeout' => RoleBasedSessionTimeout::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
     ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'message' => 'Record not found.',
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => 'Record not found.',
            ], 404);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.',
            ], 404);
        });

        $exceptions->renderable(function (PostTooLargeException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded file is too large.',
                'errors' => [
                    'file' => [
                        'Maximum allowed size is 5MB.'
                    ]
                ]
            ], 413);
        });

        $exceptions->renderable(function (InvalidFilterQuery $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        });

        $exceptions->renderable(function (LockedConfigItemException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->violations
            ], 422);
        });

        $exceptions->renderable(function (VersionConflictException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'conflicts' => $e->conflicts
            ], 409);
        });

        $exceptions->renderable(function (InvalidConfigIdsException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->violations
            ], 422);
        });
    })->create();
