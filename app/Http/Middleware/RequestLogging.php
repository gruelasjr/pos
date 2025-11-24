<?php

/**
 * Logs HTTP requests with timing and metadata.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Middleware
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware that logs incoming HTTP requests and timing metadata.
 *
 * Records method, path, status and duration for observability.
 */
/**
 * Middleware: request logging.
 *
 * Logs incoming HTTP requests for audit and debugging purposes.
 *
 * @package   App\Http\Middleware
 */
class RequestLogging
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        Log::channel('stack')->info('http_request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'request_id' => app()->bound('request-id') ? app('request-id') : null,
            'user_id' => optional($request->user())->id,
        ]);

        return $response;
    }
}
