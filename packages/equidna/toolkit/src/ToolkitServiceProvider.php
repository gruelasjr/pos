<?php

namespace Equidna\Toolkit;

use Equidna\Toolkit\Http\Middleware\RequestContext;
use Equidna\Toolkit\Support\ApiResponse;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Response::macro('equidnaSuccess', function (mixed $data = null, array $meta = []): JsonResponse {
            return response()->json(ApiResponse::success($data, $meta));
        });

        Response::macro('equidnaError', function (string $code, string $message, int $status = 400, mixed $details = null): JsonResponse {
            return response()->json(ApiResponse::error($code, $message, $details), $status);
        });

        $kernel = $this->app->make(Kernel::class);
        $kernel->appendMiddlewareToGroup('api', RequestContext::class);
    }
}
