<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestLogging;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Equidna\Toolkit\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(HandleInertiaRequests::class);
        $middleware->append(RequestLogging::class);
        $middleware->appendToGroup('api', ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return ResponseHelper::handleException($e);
            }
        });
    })->create();
