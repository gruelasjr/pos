<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestLogging;
use Equdna\SwiftAuth\Http\Middleware\EnsureAbility;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(HandleInertiaRequests::class);

        $middleware->alias([
            'ability' => EnsureAbility::class,
        ]);

        $middleware->append(RequestLogging::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $code = method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 'error';

                return response()->equidnaError(
                    is_string($code) ? $code : 'error',
                    $e->getMessage() ?: 'Error inesperado',
                    $status
                );
            }
        });
    })->create();
