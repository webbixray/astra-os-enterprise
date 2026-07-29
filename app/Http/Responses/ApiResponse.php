<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Standard structure for a successful response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @param  array<string, mixed>  $extra
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        array $extra = [],
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => null,
        ];

        if (! empty($extra)) {
            $response = array_merge($response, $extra);
        }

        return response()->json($response, $code);
    }

    /**
     * Resource created (201).
     *
     * @param  mixed  $data
     * @param  string  $message
     */
    protected function created(
        mixed $data = null,
        string $message = 'Created',
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * No content (204).
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Standard error response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array<string, mixed>  $errors
     */
    protected function error(
        string $message = 'Error',
        int $code = 400,
        array $errors = [],
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => ! empty($errors) ? (object) $errors : null,
            'meta' => null,
        ];

        return response()->json($response, $code);
    }

    /**
     * Resource not found (404).
     *
     * @param  string  $message
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Unauthenticated (401).
     *
     * @param  string  $message
     */
    protected function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Forbidden (403).
     *
     * @param  string  $message
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Validation error (422).
     *
     * @param  array<string, array<string>>  $errors
     * @param  string  $message
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed',
    ): JsonResponse {
        return $this->error($message, 422, $errors);
    }

    /**
     * Paginated response with meta information.
     *
     * @param  mixed  $items
     * @param  int  $total
     * @param  int  $page
     * @param  int  $perPage
     * @param  string  $message
     */
    protected function paginated(
        mixed $items,
        int $total,
        int $page,
        int $perPage,
        string $message = 'Success',
    ): JsonResponse {
        $lastPage = (int) ceil($total / max($perPage, 1));

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'errors' => null,
            'meta' => [
                'current_page' => $page,
                'from' => ($page - 1) * $perPage + 1,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => min($page * $perPage, $total),
                'total' => $total,
            ],
        ]);
    }

    /**
     * Paginated response using Laravel's LengthAwarePaginator.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $paginator
     * @param  string  $message
     */
    protected function paginatedFromPaginator(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
    ): JsonResponse {
        return $this->paginated(
            items: $paginator->items(),
            total: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            message: $message,
        );
    }
}
