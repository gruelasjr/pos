<?php

/**
 * Controller: Base API controller.
 *
 * Provides common helper responses and shared logic for API controllers.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

/**
 * Base API controller utilities.
 *
 * Provides common response helpers for API controllers.
 *
 * PHP 8.1+
 *
 * @package   App\Http\Controllers\API\V1
 */

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Shared response helpers for API controllers.
 */
abstract class BaseApiController extends Controller
{
    /**
     * Build a success JSON response.
     *
     * @param  string $message
     * @param  mixed  $data
     * @param  array  $headers
     * @return JsonResponse
     */
    protected function success(string $message, mixed $data = null, array $headers = []): JsonResponse
    {
        /** @var JsonResponse $response */
        $response = ResponseHelper::success($message, $data, $headers);

        return $response;
    }

    /**
     * Build an error JSON response with a mapped status helper.
     *
     * @param  string $message
     * @param  array  $errors
     * @param  int    $status
     * @return JsonResponse
     */
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

    /**
     * Return a paginated JSON response using the helper.
     *
     * @param  LengthAwarePaginator $paginator
     * @param  string               $message
     * @return JsonResponse
     */
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
