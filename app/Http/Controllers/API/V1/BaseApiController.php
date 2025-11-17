<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function success(string $message, mixed $data = null, array $headers = []): JsonResponse
    {
        /** @var JsonResponse $response */
        $response = ResponseHelper::success($message, $data, $headers);

        return $response;
    }

    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        /** @var JsonResponse $response */
        $response = match ($status) {
            ResponseHelper::HTTP_UNAUTHORIZED => ResponseHelper::unauthorized($message, $errors),
            ResponseHelper::HTTP_FORBIDDEN => ResponseHelper::forbidden($message, $errors),
            ResponseHelper::HTTP_NOT_FOUND => ResponseHelper::notFound($message, $errors),
            ResponseHelper::HTTP_CONFLICT => ResponseHelper::conflict($message, $errors),
            ResponseHelper::HTTP_UNPROCESSABLE_ENTITY => ResponseHelper::unprocessableEntity($message, $errors),
            default => ResponseHelper::badRequest($message, $errors),
        };

        return $response;
    }

    protected function paginated(LengthAwarePaginator $paginator, string $message = 'Listado paginado'): JsonResponse
    {
        return $this->success($message, [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
