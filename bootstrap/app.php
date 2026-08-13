<?php

/**
 * Application bootstrap factory.
 *
 * Configures application routing, middleware and exception handling.
 *
 * PHP 8.1+
 *
 * @package   Bootstrap
 */

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\E2eCaronteAuthentication;
use App\Http\Middleware\EnsureIdempotency;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\RequestLogging;
use App\Http\Middleware\SyncCaronteUser;
use App\Http\Middleware\UseCaronteSessionToken;
use Equidna\Toolkit\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Ometra\Caronte\Http\Middleware\ResolveApplicationContext;
use Ometra\Caronte\Http\Middleware\ValidateUserToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'caronte.user' => SyncCaronteUser::class,
            'role' => EnsureRole::class,
            'idempotency' => EnsureIdempotency::class,
        ]);

        $middleware->web(HandleInertiaRequests::class);
        $middleware->append(RequestLogging::class);
        $middleware->appendToGroup('api', ForceJsonResponse::class);

        // Tenant context and the shadow user must exist before scoped route
        // model binding; otherwise a tenant-aware model tries to resolve
        // Caronte before authentication has run.
        $middleware->prependToPriorityList(SubstituteBindings::class, E2eCaronteAuthentication::class);
        $middleware->appendToPriorityList(StartSession::class, UseCaronteSessionToken::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, ValidateUserToken::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolveApplicationContext::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, SyncCaronteUser::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Los datos enviados no son válidos.',
                        'data' => null,
                        'error' => ['code' => 'validation_error', 'message' => $e->getMessage(), 'details' => $e->errors()],
                    ], 422);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage() ?: 'La solicitud no pudo completarse.',
                        'data' => null,
                        'error' => ['code' => 'http_error', 'message' => $e->getMessage()],
                    ], $e->getStatusCode());
                }
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'server_error',
                        'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                    ],
                ], ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500);
            }
        });
    })->create();
