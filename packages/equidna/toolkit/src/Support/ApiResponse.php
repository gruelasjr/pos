<?php

namespace Equidna\Toolkit\Support;

class ApiResponse
{
    public static function success(mixed $data = null, array $meta = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'error' => null,
        ];
    }

    public static function error(string $code, string $message, mixed $details = null): array
    {
        return [
            'success' => false,
            'data' => null,
            'meta' => [],
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ];
    }
}
