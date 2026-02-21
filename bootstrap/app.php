<?php

/**
 * Bootstrap: framework application instance.
 *
 * Creates and configures the Laravel application instance used by the
 * front controllers and CLI commands.
 *
 * PHP 8.1+
 *
 * @package   Bootstrap
 */

/**
 * Application bootstrap file.
 *
 * Creates and configures the application instance.
 *
 * PHP 8.1+
 *
 * @package   Bootstrap
 */

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
use App\Http\Middleware\EnsureIdempotency;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\RequestLogging;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Equidna\Toolkit\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'idempotency' => EnsureIdempotency::class,
        ]);

        $middleware->web(HandleInertiaRequests::class);
        $middleware->append(RequestLogging::class);
        $middleware->appendToGroup('api', ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                return ResponseHelper::handleException($e);
            }
        });
    })->create();
