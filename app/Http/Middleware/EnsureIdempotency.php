<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Idempotency-Key');
        if (!$key) {
            return new JsonResponse([
                'success' => false,
                'error' => ['code' => 'idempotency_required', 'message' => 'X-Idempotency-Key es requerido'],
            ], 422);
        }

        $hash = hash('sha256', json_encode([
            'path' => $request->path(),
            'method' => $request->method(),
            'body' => $request->all(),
            'user_id' => optional($request->user())->id,
        ]));

        $existing = IdempotencyKey::query()->where('key', $key)->first();
        if ($existing) {
            if ($existing->request_hash !== $hash) {
                return new JsonResponse([
                    'success' => false,
                    'error' => ['code' => 'idempotency_hash_mismatch', 'message' => 'La llave ya fue usada con otro payload'],
                ], 409);
            }

            return new JsonResponse($existing->response_body, $existing->status_code);
        }

        /** @var Response $response */
        $response = $next($request);

        $body = json_decode($response->getContent() ?: '{}', true);
        if (!is_array($body)) {
            $body = ['raw' => $response->getContent()];
        }

        IdempotencyKey::create([
            'key' => $key,
            'route' => $request->path(),
            'method' => $request->method(),
            'request_hash' => $hash,
            'response_body' => $body,
            'status_code' => $response->getStatusCode(),
            'user_id' => optional($request->user())->id,
        ]);

        return $response;
    }
}
