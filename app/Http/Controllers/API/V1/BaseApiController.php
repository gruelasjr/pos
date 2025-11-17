<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function success(mixed $data = null, array $meta = []): JsonResponse
    {
        return response()->equidnaSuccess($data, $meta);
    }

    protected function error(string $code, string $message, int $status = 400, mixed $details = null): JsonResponse
    {
        return response()->equidnaError($code, $message, $status, $details);
    }

    protected function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return $this->success($paginator->items(), [
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
