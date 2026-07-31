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
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = trim((string) $request->headers->get('X-Request-ID'));
        if ($requestId === '' || strlen($requestId) > 128) {
            $requestId = (string) Str::uuid();
        }

        app()->instance('request-id', $requestId);
        $request->attributes->set('request_id', $requestId);

        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        $tenantId = null;
        $tenantContext = 'Equidna\\BeeHive\\Tenancy\\TenantContext';
        if (class_exists($tenantContext) && app()->bound($tenantContext)) {
            try {
                $tenantId = app($tenantContext)->get();
            } catch (\Throwable) {
                // Public probes and pre-authentication requests have no tenant.
                $tenantId = null;
            }
        }

        Log::channel('stack')->info('http_request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
            'request_id' => $requestId,
            'tenant_id' => $tenantId,
            'user_id' => optional($request->user())->getAuthIdentifier(),
        ]);

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
